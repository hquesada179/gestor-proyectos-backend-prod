<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // Desactivar planes anteriores que ya no se ofrecen (protege registros existentes)
        Plan::whereIn('slug', ['basic', 'professional'])->update(['active' => false]);

        $plans = [
            [
                'name'               => 'Gratis',
                'slug'               => 'free',
                'monthly_price'      => 0.00,
                'monthly_ai_credits' => 50,
                'daily_ai_limit'     => 20,
                'minute_ai_limit'    => 2,
                'max_users'          => 1,
                'max_projects'       => 2,
                'is_custom'          => false,
                'active'             => true,
            ],
            [
                'name'               => 'Personal',
                'slug'               => 'personal',
                'monthly_price'      => 9.99,
                'monthly_ai_credits' => 300,
                'daily_ai_limit'     => 80,
                'minute_ai_limit'    => 5,
                'max_users'          => 1,
                'max_projects'       => 10,
                'is_custom'          => false,
                'active'             => true,
            ],
            [
                'name'               => 'Equipo',
                'slug'               => 'team',
                'monthly_price'      => 29.99,
                'monthly_ai_credits' => 1500,
                'daily_ai_limit'     => 300,
                'minute_ai_limit'    => 10,
                'max_users'          => 5,
                'max_projects'       => 30,
                'is_custom'          => false,
                'active'             => true,
            ],
            [
                'name'               => 'Empresa',
                'slug'               => 'business',
                'monthly_price'      => 79.99,
                'monthly_ai_credits' => 5000,
                'daily_ai_limit'     => 1000,
                'minute_ai_limit'    => 20,
                'max_users'          => 15,
                'max_projects'       => 100,
                'is_custom'          => false,
                'active'             => true,
            ],
            [
                'name'               => 'Enterprise',
                'slug'               => 'enterprise',
                'monthly_price'      => 0.00,   // precio negociado, no se muestra
                'monthly_ai_credits' => 99999,
                'daily_ai_limit'     => 9999,
                'minute_ai_limit'    => 100,
                'max_users'          => 9999,
                'max_projects'       => 9999,
                'is_custom'          => true,
                'active'             => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        $this->command->info('Plans seeded: free, personal, team, business, enterprise');
        $this->command->warn('Plans basic and professional marked as inactive (existing subscribers preserved).');
    }
}
