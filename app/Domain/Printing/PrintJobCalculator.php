<?php

declare(strict_types=1);

namespace App\Domain\Printing;

/**
 * Pure, model-agnostic calculation service for print job pricing.
 *
 * This service calculates costs, pricing, and profit for 3D print jobs
 * based on material usage, energy consumption, labor, and markup rules.
 *
 * All calculations follow the specification:
 * - Per-component rounding to 2 decimals before summing
 * - Division by zero protection
 * - Higher precision for intermediate kWh calculations
 */
class PrintJobCalculator
{
    /**
     * Calculate all totals, costs, pricing, and profit for a print job.
     *
     * @param array<string, mixed> $input Input data containing:
     *                                    - Job inputs: pieces_per_plate, plates, grams_per_plate, hours_per_plate, labor_hours, is_first_time_order, avance_pct_override
     *                                    - Settings: electricity_rate_dkk_per_kwh, wage_rate_dkk_per_hour, first_time_fee_dkk, default_avance_pct
     *                                    - Material: price_per_kg_dkk, waste_factor_pct
     *                                    - Material type: avg_kwh_per_hour
     *
     * @return array<string, array<string, float>> Structured output with keys:
     *                                             - totals: total_pieces, total_grams, total_print_hours, kwh
     *                                             - costs: material_cost, material_cost_with_waste, power_cost, labor_cost, first_time_fee_applied, total_cost
     *                                             - pricing: applied_avance_pct, sales_price, price_per_piece (cost per piece, exclusive of avance)
     *                                             - profit: profit, profit_per_piece (inclusive of avance)
     */
    public function calculate(array $input): array
    {
        // Calculate derived totals
        $totals = $this->calculateTotals($input);

        // Calculate costs
        $costs = $this->calculateCosts($input, $totals);

        // Calculate pricing
        $pricing = $this->calculatePricing($input, $costs, $totals);

        // Calculate profit
        $profit = $this->calculateProfit($costs, $pricing, $totals);

        return [
            'totals' => $totals,
            'costs' => $costs,
            'pricing' => $pricing,
            'profit' => $profit,
        ];
    }

    /**
     * Calculate derived totals (pieces, grams, hours, kWh).
     *
     * @param  array<string, mixed> $input
     * @return array<string, float>
     */
    private function calculateTotals(array $input): array
    {
        $piecesPerPlate = (float) ($input['pieces_per_plate'] ?? 0);
        $plates = (float) ($input['plates'] ?? 0);
        $gramsPerPlate = (float) ($input['grams_per_plate'] ?? 0);
        $hoursPerPlate = (float) ($input['hours_per_plate'] ?? 0);
        $avgKwhPerHour = (float) ($input['avg_kwh_per_hour'] ?? 0);

        $totalPieces = $piecesPerPlate * $plates;
        $totalGrams = $gramsPerPlate * $plates;
        $totalPrintHours = $hoursPerPlate * $plates;

        // kWh calculation with higher precision internally
        $kwh = $totalPrintHours * $avgKwhPerHour;

        return [
            'total_pieces' => $totalPieces,
            'total_grams' => $totalGrams,
            'total_print_hours' => $totalPrintHours,
            'kwh' => $kwh,
        ];
    }

    /**
     * Calculate all cost components.
     *
     * @param  array<string, mixed> $input
     * @param  array<string, float> $totals
     * @return array<string, float>
     */
    private function calculateCosts(array $input, array $totals): array
    {
        // Material cost
        $materialCost = $this->calculateMaterialCost($input, $totals);
        $materialCostWithWaste = $this->calculateMaterialCostWithWaste($input, $materialCost);

        // Power cost
        $powerCost = $this->calculatePowerCost($input, $totals);

        // Labor cost
        $laborCost = $this->calculateLaborCost($input);

        // First-time fee
        $firstTimeFeeApplied = $this->calculateFirstTimeFee($input);

        // Total cost (sum of rounded components, then rounded again)
        $totalCost = \round(
            \round($materialCostWithWaste, 2)
            + \round($powerCost, 2)
            + \round($laborCost, 2)
            + \round($firstTimeFeeApplied, 2),
            2,
        );

        return [
            'material_cost' => \round($materialCost, 2),
            'material_cost_with_waste' => \round($materialCostWithWaste, 2),
            'power_cost' => \round($powerCost, 2),
            'labor_cost' => \round($laborCost, 2),
            'first_time_fee_applied' => \round($firstTimeFeeApplied, 2),
            'total_cost' => $totalCost,
        ];
    }

    /**
     * Calculate material cost (before waste).
     *
     * @param array<string, mixed> $input
     * @param array<string, float> $totals
     */
    private function calculateMaterialCost(array $input, array $totals): float
    {
        $pricePerKgDkk = (float) ($input['price_per_kg_dkk'] ?? 0);
        $totalGrams = $totals['total_grams'];

        $materialPricePerG = $pricePerKgDkk / 1000;
        $materialCost = $totalGrams * $materialPricePerG;

        return \round($materialCost, 2);
    }

    /**
     * Calculate material cost with waste factor applied.
     *
     * @param array<string, mixed> $input
     */
    private function calculateMaterialCostWithWaste(array $input, float $materialCost): float
    {
        $wasteFactorPct = (float) ($input['waste_factor_pct'] ?? 0);
        $materialCostWithWaste = $materialCost * (1 + $wasteFactorPct / 100);

        return \round($materialCostWithWaste, 2);
    }

    /**
     * Calculate power cost from kWh consumption.
     *
     * @param array<string, mixed> $input
     * @param array<string, float> $totals
     */
    private function calculatePowerCost(array $input, array $totals): float
    {
        $kwh = $totals['kwh'];
        $electricityRateDkkPerKwh = (float) ($input['electricity_rate_dkk_per_kwh'] ?? 0);
        $powerCost = $kwh * $electricityRateDkkPerKwh;

        return \round($powerCost, 2);
    }

    /**
     * Calculate labor cost.
     *
     * @param array<string, mixed> $input
     */
    private function calculateLaborCost(array $input): float
    {
        $laborHours = (float) ($input['labor_hours'] ?? 0);
        $wageRateDkkPerHour = (float) ($input['wage_rate_dkk_per_hour'] ?? 0);
        $laborCost = $laborHours * $wageRateDkkPerHour;

        return \round($laborCost, 2);
    }

    /**
     * Calculate first-time fee if applicable.
     *
     * @param array<string, mixed> $input
     */
    private function calculateFirstTimeFee(array $input): float
    {
        $isFirstTimeOrder = (bool) ($input['is_first_time_order'] ?? false);
        $firstTimeFeeDkk = (float) ($input['first_time_fee_dkk'] ?? 0);

        return $isFirstTimeOrder ? $firstTimeFeeDkk : 0.00;
    }

    /**
     * Calculate pricing (markup and sales price).
     *
     * @param  array<string, mixed> $input
     * @param  array<string, float> $costs
     * @param  array<string, float> $totals
     * @return array<string, float>
     */
    private function calculatePricing(array $input, array $costs, array $totals): array
    {
        $defaultAvancePct = (float) ($input['default_avance_pct'] ?? 0);
        $avancePctOverride = isset($input['avance_pct_override']) ? (float) $input['avance_pct_override'] : null;

        $appliedAvancePct = $avancePctOverride ?? $defaultAvancePct;
        $totalCost = $costs['total_cost'];
        $salesPrice = $totalCost * (1 + $appliedAvancePct / 100);

        return [
            'applied_avance_pct' => $appliedAvancePct,
            'sales_price' => \round($salesPrice, 2),
            'price_per_piece' => $this->calculatePricePerPiece($totalCost, $totals),
        ];
    }

    /**
     * Calculate price per piece (cost per piece, exclusive of avance).
     * This shows the raw cost per piece before markup.
     *
     * @param array<string, float> $totals
     */
    private function calculatePricePerPiece(float $totalCost, array $totals): float
    {
        $totalPieces = $totals['total_pieces'];

        if ($totalPieces == 0) {
            return 0.00;
        }

        return \round($totalCost / $totalPieces, 2);
    }

    /**
     * Calculate profit and profit per piece.
     *
     * @param  array<string, float> $costs
     * @param  array<string, float> $pricing
     * @param  array<string, float> $totals
     * @return array<string, float>
     */
    private function calculateProfit(array $costs, array $pricing, array $totals): array
    {
        $salesPrice = $pricing['sales_price'];
        $totalCost = $costs['total_cost'];
        $profit = $salesPrice - $totalCost;
        $totalPieces = $totals['total_pieces'];

        $profitPerPiece = $totalPieces == 0 ? 0.00 : \round($profit / $totalPieces, 2);

        return [
            'profit' => \round($profit, 2),
            'profit_per_piece' => $profitPerPiece,
        ];
    }
}
