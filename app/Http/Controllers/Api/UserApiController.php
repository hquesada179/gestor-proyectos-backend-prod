<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['plan', 'aiCredit']);

        return response()->json([
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'firebase_uid' => $user->firebase_uid,
            'plan_id'      => $user->plan_id,
            'plan'         => $user->plan ? [
                'id'                 => $user->plan->id,
                'name'               => $user->plan->name,
                'slug'               => $user->plan->slug,
                'monthly_price'      => $user->plan->monthly_price,
                'max_projects'       => $user->plan->max_projects,
                'monthly_ai_credits' => $user->plan->monthly_ai_credits,
            ] : null,
            'ai_credits' => $user->aiCredit ? [
                'available'      => $user->aiCredit->credits_available,
                'used'           => $user->aiCredit->credits_used,
                'period_ends_at' => $user->aiCredit->period_ends_at?->toDateString(),
            ] : null,
        ]);
    }
}
