@php
    use App\Support\Format;
@endphp

<div class="calculation-panel" style="margin-top: 30px; padding: 20px; background-color: #f0f8ff; border-radius: 4px; border: 2px solid {{ $isLocked ? '#28a745' : '#ffc107' }};">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
        <h2 style="margin: 0; font-size: 1.3em;">
            Calculation Results
            @if($isLocked)
                <span class="badge" style="background-color: #28a745; color: #fff; padding: 4px 12px; border-radius: 4px; font-size: 0.7em; margin-left: 10px;">
                    <i class="fa fa-lock"></i> Locked
                </span>
            @else
                <span class="badge" style="background-color: #ffc107; color: #000; padding: 4px 12px; border-radius: 4px; font-size: 0.7em; margin-left: 10px;">
                    <i class="fa fa-file"></i> Draft
                </span>
            @endif
        </h2>
    </div>

    @if($calculation)
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <!-- Totals Section -->
            <div class="calculation-section" style="background-color: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3 style="font-size: 1.1em; margin-top: 0; margin-bottom: 12px; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 8px;">Totals</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div>
                        <strong style="color: #666;">Total Pieces:</strong>
                        <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                            {{ Format::integer((int)($calculation['totals']['total_pieces'] ?? 0)) }}
                        </div>
                    </div>
                    <div>
                        <strong style="color: #666;">Total Grams:</strong>
                        <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                            {{ Format::number($calculation['totals']['total_grams'] ?? 0) }}
                        </div>
                    </div>
                    <div>
                        <strong style="color: #666;">Total Print Hours:</strong>
                        <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                            {{ Format::number($calculation['totals']['total_print_hours'] ?? 0, 3) }}
                        </div>
                    </div>
                    <div>
                        <strong style="color: #666;">kWh:</strong>
                        <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                            {{ Format::number($calculation['totals']['kwh'] ?? 0, 2) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Costs Section -->
            <div class="calculation-section" style="background-color: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3 style="font-size: 1.1em; margin-top: 0; margin-bottom: 12px; color: #333; border-bottom: 2px solid #dc3545; padding-bottom: 8px;">Costs</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div>
                        <strong style="color: #666;">Material Cost:</strong>
                        <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                            {{ Format::dkk($calculation['costs']['material_cost'] ?? 0) }}
                        </div>
                    </div>
                    <div>
                        <strong style="color: #666;">Material Cost (with Waste):</strong>
                        <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                            {{ Format::dkk($calculation['costs']['material_cost_with_waste'] ?? 0) }}
                        </div>
                    </div>
                    <div>
                        <strong style="color: #666;">Power Cost:</strong>
                        <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                            {{ Format::dkk($calculation['costs']['power_cost'] ?? 0) }}
                        </div>
                    </div>
                    <div>
                        <strong style="color: #666;">Labor Cost:</strong>
                        <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                            {{ Format::dkk($calculation['costs']['labor_cost'] ?? 0) }}
                        </div>
                    </div>
                    <div>
                        <strong style="color: #666;">First Time Fee:</strong>
                        <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                            {{ Format::dkk($calculation['costs']['first_time_fee_applied'] ?? 0) }}
                        </div>
                    </div>
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 2px solid #ddd;">
                        <strong style="color: #333; font-size: 1.1em;">Total Cost:</strong>
                        <div style="font-size: 1.3em; color: #dc3545; font-weight: bold; margin-top: 4px;">
                            {{ Format::dkk($calculation['costs']['total_cost'] ?? 0) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Section -->
            <div class="calculation-section" style="background-color: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3 style="font-size: 1.1em; margin-top: 0; margin-bottom: 12px; color: #333; border-bottom: 2px solid #28a745; padding-bottom: 8px;">Pricing</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div>
                        <strong style="color: #666;">Applied Avance %:</strong>
                        <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                            {{ Format::pct($calculation['pricing']['applied_avance_pct'] ?? 0) }}
                        </div>
                    </div>
                    <div>
                        <strong style="color: #666;">Sales Price:</strong>
                        <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                            {{ Format::dkk($calculation['pricing']['sales_price'] ?? 0) }}
                        </div>
                    </div>
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 2px solid #ddd;">
                        <strong style="color: #333; font-size: 1.1em;">Price per Piece:</strong>
                        <div style="font-size: 1.3em; color: #28a745; font-weight: bold; margin-top: 4px;">
                            {{ Format::dkk($calculation['pricing']['price_per_piece'] ?? 0) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profit Section -->
            <div class="calculation-section" style="background-color: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3 style="font-size: 1.1em; margin-top: 0; margin-bottom: 12px; color: #333; border-bottom: 2px solid #17a2b8; padding-bottom: 8px;">Profit</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div>
                        <strong style="color: #666;">Profit:</strong>
                        <div style="font-size: 1.1em; color: #333; margin-top: 4px;">
                            {{ Format::dkk($calculation['profit']['profit'] ?? 0) }}
                        </div>
                    </div>
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 2px solid #ddd;">
                        <strong style="color: #333; font-size: 1.1em;">Profit per Piece:</strong>
                        <div style="font-size: 1.3em; color: #17a2b8; font-weight: bold; margin-top: 4px;">
                            {{ Format::dkk($calculation['profit']['profit_per_piece'] ?? 0) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div style="padding: 20px; text-align: center; color: #777;">
            <p style="margin: 0;">No calculation data available. Please fill in the required fields.</p>
        </div>
    @endif
</div>

