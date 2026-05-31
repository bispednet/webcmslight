<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class ConversationAnalyticsBuilder
{
    private CommercialReportBuilder $reportBuilder;

    public function __construct()
    {
        $this->reportBuilder = new CommercialReportBuilder();
    }

    public function build(ConversationMemory $memory): array
    {
        return $this->reportBuilder->buildAnalytics($memory);
    }
}
