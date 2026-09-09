<?php

/**
 * Service to handle pricing, VAT, and unit calculations for courses.
 */
class CoursePricingService
{
    public const DEFAULT_VAT_RATE = 0.20; // 20% österreichische USt

    /**
     * Calculate gross price based on net price and VAT rate.
     */
    public function calculateGrossPrice(float $netPrice, float $vatRate = self::DEFAULT_VAT_RATE): float
    {
        return round($netPrice * (1 + $vatRate), 2);
    }

    /**
     * Calculate price per single learning unit (LE).
     */
    public function calculatePricePerLE(float $grossPrice, float $totalLE): float
    {
        if ($totalLE <= 0) {
            return 0.0;
        }

        return round($grossPrice / $totalLE, 2);
    }

    /**
     * Format a price according to Austrian currency standards (e.g., "1.490,00").
     */
    public function formatCurrency(float $amount, string $decPoint = ',', string $thousandsSep = '.'): string
    {
        return number_format($amount, 2, $decPoint, $thousandsSep);
    }
}
