<?php

class InventoryController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $model = new Product();

        $this->view('inventory/index', [
            'products' => $model->all(),
            'lowStock' => $model->lowStock(),
            'valuation' => $model->stockValuation(),
            'salesSummary' => $model->salesSummary(),
        ]);
    }
}
