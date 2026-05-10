<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::where('active', true)
            ->orderBy('monthly_price')
            ->get();

        $userPlanId = auth()->user()->plan_id;

        return view('plans.index', compact('plans', 'userPlanId'));
    }
}
