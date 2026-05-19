<?php

declare(strict_types=1);

namespace App\Services\Fastmail;

use Carbon\CarbonInterface;

final class EmailQuery
{
    private ?string $mailboxId = null;

    private ?CarbonInterface $receivedAfter = null;

    private ?CarbonInterface $receivedBefore = null;

    private ?string $from = null;

    private ?string $to = null;

    private ?string $subject = null;

    private ?bool $hasAttachment = null;

    private ?string $text = null;

    private int $limit = 25;

    private int $position = 0;

    private ?string $queryState = null;

    private ?string $recipientScope = null;

    /**
     * Restrict results to messages delivered to this address (To or Cc).
     */
    public function scopedToRecipient(string $email): self
    {
        $clone = clone $this;
        $clone->recipientScope = $email;

        return $clone;
    }

    public function scopedToConfiguredRecipient(): self
    {
        $email = \config('fastmail.email');

        if (!\is_string($email) || $email === '') {
            return $this;
        }

        return $this->scopedToRecipient($email);
    }

    public function inMailbox(string $mailboxId): self
    {
        $clone = clone $this;
        $clone->mailboxId = $mailboxId;

        return $clone;
    }

    public function receivedAfter(CarbonInterface $date): self
    {
        $clone = clone $this;
        $clone->receivedAfter = $date;

        return $clone;
    }

    public function receivedBefore(CarbonInterface $date): self
    {
        $clone = clone $this;
        $clone->receivedBefore = $date;

        return $clone;
    }

    public function from(string $address): self
    {
        $clone = clone $this;
        $clone->from = $address;

        return $clone;
    }

    public function to(string $address): self
    {
        $clone = clone $this;
        $clone->to = $address;

        return $clone;
    }

    public function subject(string $text): self
    {
        $clone = clone $this;
        $clone->subject = $text;

        return $clone;
    }

    public function hasAttachment(bool $value = true): self
    {
        $clone = clone $this;
        $clone->hasAttachment = $value;

        return $clone;
    }

    public function text(string $query): self
    {
        $clone = clone $this;
        $clone->text = $query;

        return $clone;
    }

    public function limit(int $limit): self
    {
        $clone = clone $this;
        $clone->limit = $limit;

        return $clone;
    }

    public function position(int $position): self
    {
        $clone = clone $this;
        $clone->position = $position;

        return $clone;
    }

    public function queryState(?string $queryState): self
    {
        $clone = clone $this;
        $clone->queryState = $queryState;

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    public function toFilter(): array
    {
        $filter = $this->buildSimpleFilter();

        if ($this->recipientScope === null || $this->recipientScope === '') {
            return $filter;
        }

        $recipientFilter = [
            'operator' => 'OR',
            'conditions' => [
                ['to' => $this->recipientScope],
                ['cc' => $this->recipientScope],
            ],
        ];

        if ($filter === []) {
            return $recipientFilter;
        }

        return [
            'operator' => 'AND',
            'conditions' => [$recipientFilter, $filter],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArguments(): array
    {
        $arguments = [
            'limit' => $this->limit,
            'position' => $this->position,
            'sort' => [['property' => 'receivedAt', 'isAscending' => false]],
        ];

        $filter = $this->toFilter();

        if ($filter !== []) {
            $arguments['filter'] = $filter;
        }

        if ($this->queryState !== null && $this->queryState !== '') {
            $arguments['queryState'] = $this->queryState;
        }

        return $arguments;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSimpleFilter(): array
    {
        $filter = [];

        if ($this->mailboxId !== null) {
            $filter['inMailbox'] = $this->mailboxId;
        }

        if ($this->receivedAfter !== null) {
            $filter['after'] = $this->receivedAfter->toIso8601String();
        }

        if ($this->receivedBefore !== null) {
            $filter['before'] = $this->receivedBefore->toIso8601String();
        }

        if ($this->from !== null && $this->from !== '') {
            $filter['from'] = $this->from;
        }

        if ($this->to !== null && $this->to !== '') {
            $filter['to'] = $this->to;
        }

        if ($this->subject !== null && $this->subject !== '') {
            $filter['subject'] = $this->subject;
        }

        if ($this->hasAttachment !== null) {
            $filter['hasAttachment'] = $this->hasAttachment;
        }

        if ($this->text !== null && $this->text !== '') {
            $filter['text'] = $this->text;
        }

        return $filter;
    }
}
