<?php

namespace Gilbitron\Laravel\Spark\Console\Commands;

use Stripe;
use Illuminate\Console\Command;
use Laravel\Spark\Spark;

class CreateStripePlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spark:create-stripe-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates plans in Stripe based on the plans defined in Spark';

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
     * @return mixed
     */
    public function handle()
    {
        Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $this->info('Creating user plans...');
        $this->createStripePlans(Spark::$plans);

        $this->info('Creating team plans...');
        $this->createStripePlans(Spark::$teamPlans);

        $this->info('Finished');
    }

    /**
     * Try and create plans in Stripe
     *
     * @param array $plans
     */
    protected function createStripePlans($plans)
    {
        foreach ($plans as $plan) {
            if ($this->planExists($plan)) {
                $this->line('Stripe plan ' . $plan->id . ' already exists');
            } else {
                Stripe\Plan::create([
                    'id'                   => $plan->id,
                    'name'                 => Spark::$details['product'] . ' ' . $plan->name . ' ($' . $plan->price .
                                              ' ' . $plan->interval . ')',
                    'amount'               => $plan->price * 100,
                    'interval'             => str_replace('ly', '', $plan->interval),
                    'currency'             => 'usd',
                    'statement_descriptor' => Spark::$details['vendor'],
                    'trial_period_days'    => $plan->trialDays,
                ]);

                $this->info('Stripe plan created: ' . $plan->id);
            }
        }
    }

    /**
     * Check if a plan already exists
     *
     * @param $plan
     * @return bool
     */
    private function planExists($plan)
    {
        try {
            Stripe\Plan::retrieve($plan->id);
            return true;
        } catch (\Exception $e) {
        }

        return false;
    }
}
