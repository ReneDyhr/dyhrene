<?php

declare(strict_types=1);

use App\Http\Controllers\BirdnetDetectionController;
use App\Models\BirdnetDetection;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Laravel\Passport\Passport;

\uses()->group('feature');

/**
 * @return array<string, mixed>
 */
function validDetectionMetadata(string $uuid = 'd1994b9f-9876-4321-abcd-ef0123456789'): array
{
    return [
        'id' => $uuid,
        'scientific_name' => 'Turdus merula',
        'common_name' => 'Common Blackbird',
        'confidence' => 0.95,
        'start_time' => 10.5,
        'end_time' => 13.2,
        'recorded_at' => '2026-07-22T10:00:00+00:00',
        'latitude' => 52.5,
        'longitude' => 13.4,
    ];
}

\test('authenticated user can upload a detection with audio', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $audioFile = UploadedFile::fake()->create('recording.wav', 2048, 'audio/wav');

    $response = $this->withHeaders(['Accept' => 'application/json'])
        ->post('/api/species/upload', [
            'metadata' => \json_encode(\validDetectionMetadata()),
            'audio' => $audioFile,
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('message', 'Detection uploaded successfully');
    $response->assertJsonPath('detection.detection_uuid', \validDetectionMetadata()['id']);

    \expect(BirdnetDetection::query()->count())->toBe(1);
})->covers(BirdnetDetectionController::class)->group('feature');

\test('authenticated user can upload detection without audio', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $response = $this->postJson('/api/species/upload', [
        'metadata' => \json_encode(\validDetectionMetadata()),
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('message', 'Detection uploaded successfully');
    $response->assertJsonPath('detection.detection_uuid', \validDetectionMetadata()['id']);
    $response->assertJsonPath('detection.audio_path', null);

    \expect(BirdnetDetection::query()->count())->toBe(1);
})->covers(BirdnetDetectionController::class)->group('feature');

\test('duplicate detection_uuid returns 200 for same user', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $metadata = \validDetectionMetadata('duplicate-uuid-001');

    // First upload — creates the detection
    $response1 = $this->postJson('/api/species/upload', [
        'metadata' => \json_encode($metadata),
    ]);
    $response1->assertStatus(201);

    // Second upload with same UUID — returns 200 (idempotent)
    $response2 = $this->postJson('/api/species/upload', [
        'metadata' => \json_encode($metadata),
    ]);
    $response2->assertStatus(200);
    $response2->assertJsonPath('message', 'Detection already exists');

    // Only one record should exist
    \expect(BirdnetDetection::query()->count())->toBe(1);
})->covers(BirdnetDetectionController::class)->group('feature');

\test('duplicate segment_id with same start/end time returns 200 for same user', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user);

    // First upload
    $metadata1 = \validDetectionMetadata('uuid-seg-1');
    $metadata1['segment_id'] = 'segment-001';

    $response1 = $this->postJson('/api/species/upload', [
        'metadata' => \json_encode($metadata1),
    ]);
    $response1->assertStatus(201);

    // Second upload — same segment_id, start_time, end_time — but different UUID
    $metadata2 = \validDetectionMetadata('uuid-seg-2');
    $metadata2['segment_id'] = 'segment-001';
    $metadata2['start_time'] = 10.5;
    $metadata2['end_time'] = 13.2;

    $response2 = $this->postJson('/api/species/upload', [
        'metadata' => \json_encode($metadata2),
    ]);
    $response2->assertStatus(200);
    $response2->assertJsonPath('message', 'Detection already exists');

    // Only one record should exist
    \expect(BirdnetDetection::query()->count())->toBe(1);
})->covers(BirdnetDetectionController::class)->group('feature');

\test('same segment_id but different time window creates new detection', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user);

    // First upload
    $metadata1 = \validDetectionMetadata('uuid-seg-3');
    $metadata1['segment_id'] = 'segment-002';
    $metadata1['start_time'] = 10.5;
    $metadata1['end_time'] = 13.2;

    $response1 = $this->postJson('/api/species/upload', [
        'metadata' => \json_encode($metadata1),
    ]);
    $response1->assertStatus(201);

    // Second upload — same segment_id but different time window
    $metadata2 = \validDetectionMetadata('uuid-seg-4');
    $metadata2['segment_id'] = 'segment-002';
    $metadata2['start_time'] = 20.0;
    $metadata2['end_time'] = 23.0;

    $response2 = $this->postJson('/api/species/upload', [
        'metadata' => \json_encode($metadata2),
    ]);
    $response2->assertStatus(201);

    // Two records should exist
    \expect(BirdnetDetection::query()->count())->toBe(2);
})->covers(BirdnetDetectionController::class)->group('feature');

\test('no segment_id always creates new detection', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user);

    // Upload without segment_id
    $metadata = \validDetectionMetadata('uuid-seg-5');
    unset($metadata['segment_id']);

    $response1 = $this->postJson('/api/species/upload', [
        'metadata' => \json_encode($metadata),
    ]);
    $response1->assertStatus(201);

    // Second upload without segment_id, different UUID
    $metadata2 = \validDetectionMetadata('uuid-seg-6');
    unset($metadata2['segment_id']);

    $response2 = $this->postJson('/api/species/upload', [
        'metadata' => \json_encode($metadata2),
    ]);
    $response2->assertStatus(201);

    // Two records should exist (no dedup without segment_id)
    \expect(BirdnetDetection::query()->count())->toBe(2);
})->covers(BirdnetDetectionController::class)->group('feature');

\test('duplicate detection_uuid from different user returns 500 due to unique constraint', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $metadataSameUuid = \validDetectionMetadata('shared-uuid-001');

    // User A uploads
    Passport::actingAs($userA);
    $responseA = $this->postJson('/api/species/upload', [
        'metadata' => \json_encode($metadataSameUuid),
    ]);
    $responseA->assertStatus(201);

    // User B tries same UUID — DB unique constraint violated (500)
    Passport::actingAs($userB);
    $responseB = $this->postJson('/api/species/upload', [
        'metadata' => \json_encode($metadataSameUuid),
    ]);
    $responseB->assertStatus(500);

    // Only user A has the record
    \expect(BirdnetDetection::query()->where('user_id', $userA->id)->count())->toBe(1);
    \expect(BirdnetDetection::query()->where('user_id', $userB->id)->count())->toBe(0);
})->covers(BirdnetDetectionController::class)->group('feature');

\test('unauthenticated request returns 403', function (): void {
    $response = $this->postJson('/api/species/upload', [
        'metadata' => 'not-json',
    ]);

    $response->assertStatus(403);
})->covers(BirdnetDetectionController::class)->group('feature');

\test('invalid metadata JSON returns 422', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $response = $this->postJson('/api/species/upload', [
        'metadata' => 'not-valid-json{{{',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['metadata']);
})->covers(BirdnetDetectionController::class)->group('feature');

\test('metadata missing required id field returns 422', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $metadata = \validDetectionMetadata();
    unset($metadata['id']);

    $response = $this->postJson('/api/species/upload', [
        'metadata' => \json_encode($metadata),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['metadata']);
})->covers(BirdnetDetectionController::class)->group('feature');

\test('metadata missing required scientific_name field returns 422', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $metadata = \validDetectionMetadata();
    unset($metadata['scientific_name']);

    $response = $this->postJson('/api/species/upload', [
        'metadata' => \json_encode($metadata),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['metadata']);
})->covers(BirdnetDetectionController::class)->group('feature');

\test('metadata missing required recorded_at field returns 422', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $metadata = \validDetectionMetadata();
    unset($metadata['recorded_at']);

    $response = $this->postJson('/api/species/upload', [
        'metadata' => \json_encode($metadata),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['metadata']);
})->covers(BirdnetDetectionController::class)->group('feature');

\test('audio file with invalid mime type returns 422', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $pdfFile = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

    $response = $this->withHeaders(['Accept' => 'application/json'])
        ->post('/api/species/upload', [
            'metadata' => \json_encode(\validDetectionMetadata()),
            'audio' => $pdfFile,
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['audio']);
})->covers(BirdnetDetectionController::class)->group('feature');

\test('metadata field is required returns 422', function (): void {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $response = $this->postJson('/api/species/upload', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['metadata']);
})->covers(BirdnetDetectionController::class)->group('feature');
