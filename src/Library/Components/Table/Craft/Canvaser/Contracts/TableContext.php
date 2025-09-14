<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts;

/**
 * Value object yang menampung konteks legacy untuk Datatables orchestrator.
 * Tidak mengubah perilaku; hanya pembungkus data agar modul modular bisa bekerja.
 */
final class TableContext
{
    public string $tableName = 'unknown';

    /** @var array<string,mixed> */
    public array $method = [];

    /** @var object */
    public $data;

    /** @var array<string,mixed> */
    public array $request = [];

    public bool $indexLists = false;

    /** @var string[] */
    public array $blacklists = ['password', 'action', 'no'];

    public ?string $routePath = null;

    /** @param  array<string,mixed>|object  $method */
    public static function fromLegacy($method, $data, array $request): self
    {
        $self = new self();
        $self->method = is_array($method) ? $method : (array) $method;
        $self->data = $data;
        $self->request = $request;
        try {
            $name = $self->method['difta']['name'] ?? null;
            if ($name && ! empty($data->datatables->model[$name]['source'])) {
                $source = $data->datatables->model[$name]['source'];
                if (is_object($source) && method_exists($source, 'getTable')) {
                    $self->tableName = $source->getTable();
                }
            }
        } catch (\Throwable $e) {
            // fallback: biarkan 'unknown'
        }
        $self->indexLists = (bool) ($data->datatables->records['index_lists'] ?? false);
        
        // Extract route path from data if available
        $self->routePath = $data->datatables->route_path ?? null;
        
        // blacklists bisa disesuaikan dari $data bila diperlukan nanti
        return $self;
    }
}
