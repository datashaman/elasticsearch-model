<?php

namespace Datashaman\Elasticsearch\Model\Jobs;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

class Indexer implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($operation, $class, $id)
    {
        $this->operation = $operation;
        $this->class = $class;
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $class = $this->class;

        switch ($this->operation) {
        case 'index':
            $record = $class::find($this->id);
            $class::elasticsearch()->client()->index([
                'index' => $class::indexName(),
                'type' => $class::documentType(),
                'id' => $record->id,
                'body' => $record->toIndexedArray(),
            ]);
            $record->indexDocument();
            break;
        case 'delete':
            $class::elasticsearch()->client()->delete([
                'index' => $class::indexName(),
                'type' => $class::documentType(),
                'id' => $this->id,
            ]);
            break;
        default:
            throw new Exception('Unknown operation: '.$this->operation);
        }
    }
}
