<?php

namespace App\Services;

class ApplicationPreScoringService
{
    public function score(array $triageAnswers = [], array $dynamicPayload = [], array $categoryTags = []): array
    {
        $score = 0;
        $reason = null;
        $label = 'Normal';

        $keywords = strtolower(json_encode([$triageAnswers, $dynamicPayload, $categoryTags]) ?: '');

        if (str_contains($keywords, 'ward') || str_contains($keywords, 'admission')) {
            $score += 80;
            $reason = 'Health - Ward Admission';
            $label = 'High';
        }

        if (str_contains($keywords, 'disaster') || str_contains($keywords, 'flood') || str_contains($keywords, 'fire')) {
            $score += 90;
            $reason = $reason ?: 'Natural Disaster';
            $label = 'High';
        }

        if (in_array('health', $categoryTags, true)) {
            $score += 20;
        }

        if (in_array('welfare', $categoryTags, true)) {
            $score += 10;
        }

        if ($score >= 120) {
            $label = 'Critical';
        } elseif ($score >= 70) {
            $label = 'High';
        } elseif ($score >= 30) {
            $label = 'Medium';
        }

        return [
            'priority_score' => $score,
            'priority_label' => $label,
            'priority_reason' => $reason,
        ];
    }
}
