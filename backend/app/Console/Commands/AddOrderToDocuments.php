<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Document;

class AddOrderToDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:update-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add order ids to documents by suppliers';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $documents = Document::orderBy('supplier_id')->get();

        $documentsGroup = $documents->groupBy('supplier_id');

        foreach ($documentsGroup as $supplierId => $documents) {
            $orderId = 1;
            foreach ($documents as $document) {
                $document->order_id = $orderId++;
                $document->save();
            }
        }

        $this->info("Update documents");

        return 0;
    }
}
