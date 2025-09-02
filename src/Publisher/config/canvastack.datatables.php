<?php

return [
    // Feature-flag: OFF by default to keep legacy path
    'pipeline_enabled' => env('CANVASTACK_DT_PIPELINE', false),

    // Optional execution mode (documented only for now)
    // legacy | pipeline | hybrid
    'mode' => env('CANVASTACK_DT_MODE', 'legacy'),

    // Placeholder for future pipeline order override
    'pipeline_order' => [
        'ResolveModelStage',
        'BuildRelationsStage',
        'BuildFilterStage',
        'BuildOrderStage',
        'ApplyFormulaStage',
        'ApplyFormattingStage',
        'ApplyImageColumnsStage',
        'ApplyActionsStage',
        'FinalizeResponseStage',
    ],
];
