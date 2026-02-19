<?php

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $model = new Dashboard();

        $this->view('dashboard/index', [
            'metrics' => $model->metrics(),
            'chart' => $model->monthlySalesChart(),
        ]);
    }
}
