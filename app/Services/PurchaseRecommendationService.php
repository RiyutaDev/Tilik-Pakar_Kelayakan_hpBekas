<?php

namespace App\Services;

use App\Models\AssessmentResult;
use App\Models\MarketValue;

class PurchaseRecommendationService
{
    /**
     * @param  array{
     *     has_purchase_price: bool,
     *     has_repair_estimate: bool,
     *     effective_cost_min: ?string,
     *     effective_cost_max: ?string
     * }  $effectiveCost
     * @return array{
     *     recommendation: ?string,
     *     reason: string,
     *     diagnosis: ?string,
     *     certainty_factor: ?float,
     *     economic_available: bool,
     *     effective_cost_min: ?string,
     *     effective_cost_max: ?string,
     *     market_value_min: ?string,
     *     market_value_max: ?string
     * }
     */
    public function evaluate(
        AssessmentResult $assessmentResult,
        array $effectiveCost,
        ?MarketValue $marketValue
    ): array {
        $effectiveCostMin = $effectiveCost['effective_cost_min'];
        $effectiveCostMax = $effectiveCost['effective_cost_max'];
        $marketValueMin = $marketValue?->market_price_min;
        $marketValueMax = $marketValue?->market_price_max;
        $economicAvailable = $effectiveCost['has_purchase_price']
            && $effectiveCost['has_repair_estimate']
            && $effectiveCostMin !== null
            && $effectiveCostMax !== null
            && $marketValueMin !== null
            && $marketValueMax !== null;

        $recommendation = null;
        $reason = 'Evaluasi ekonomi belum dapat dilakukan karena data harga belum lengkap.';

        if ($assessmentResult->classification === 'tidak_direkomendasikan') {
            $recommendation = 'TIDAK LAYAK';
            $reason = 'Diagnosis teknis tidak merekomendasikan perangkat ini; evaluasi harga tidak mengubah kondisi tersebut.';
        } elseif ($economicAvailable && $this->minorUnits($effectiveCostMin) > $this->minorUnits($marketValueMax)) {
            $recommendation = 'TIDAK LAYAK';
            $reason = 'Bahkan total biaya efektif minimum melebihi batas atas rentang nilai pasar.';
        } elseif ($assessmentResult->classification === 'bersyarat') {
            $recommendation = 'BERSYARAT';
            $reason = $economicAvailable
                ? 'Kondisi perangkat memerlukan perhatian; temuan perlu ditangani dan diverifikasi sebelum transaksi.'
                : 'Kondisi perangkat memerlukan perhatian; data ekonomi belum lengkap untuk perbandingan harga.';
        } elseif ($assessmentResult->classification !== 'layak') {
            $recommendation = 'BERSYARAT';
            $reason = $economicAvailable
                ? 'Diagnosis teknis belum cukup terverifikasi untuk merekomendasikan pembelian.'
                : 'Diagnosis teknis belum cukup terverifikasi dan data ekonomi belum lengkap.';
        } elseif ($economicAvailable && $this->minorUnits($effectiveCostMax) < $this->minorUnits($marketValueMin)) {
            $recommendation = 'LAYAK';
            $reason = 'Kondisi perangkat layak dan seluruh rentang total biaya efektif berada di bawah rentang nilai pasar.';
        } elseif ($economicAvailable) {
            $recommendation = 'BERSYARAT';
            $reason = 'Rentang total biaya efektif dan nilai pasar overlap atau berdekatan; bandingkan kondisi unit dan pertimbangkan negosiasi.';
        }

        return [
            'recommendation' => $recommendation,
            'reason' => $reason,
            'diagnosis' => $assessmentResult->classification,
            'certainty_factor' => $assessmentResult->confidence_score === null
                ? null
                : (float) $assessmentResult->confidence_score,
            'economic_available' => $economicAvailable,
            'effective_cost_min' => $effectiveCostMin,
            'effective_cost_max' => $effectiveCostMax,
            'market_value_min' => $marketValueMin,
            'market_value_max' => $marketValueMax,
        ];
    }

    private function minorUnits(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
