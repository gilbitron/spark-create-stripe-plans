<?php

namespace Gilbitron\Laravel\Spark\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Cashier\Cashier;
use Laravel\Spark\Spark;
use Stripe;

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

        $this->info('Creating product ...');
        $this->createProduct();

        $this->info('Creating user plans...');
        $this->createStripePlans(Spark::$plans);

        $this->info('Creating team plans...');
        $this->createStripePlans(Spark::$teamPlans);

        $this->info('Finished');
    }

    protected function getProductId()
    {
        return Spark::$details['stripe_product_id']
            ?? strtolower(str_replace(' ', '-', Spark::$details['product']));
    }

    /**
     * Try and create product in Stripe
     *
     * @param array $plans
     */
    protected function createProduct()
    {
        $id = $this->getProductId();

        try {
            Stripe\Product::retrieve($id);

            $this->line('Stripe product ' . $id . ' already exists');
        } catch (\Exception $e) {
            Stripe\Product::create([
                'id'                   => $id,
                'name'                 => Spark::$details['product'],
                'statement_descriptor' => Spark::$details['vendor'],
                'unit_label'           => 'JobAds',
                'type'                 => 'service',
            ]);

            $this->info('Stripe product created: ' . $id);
        }

    }
    /**
     * Try and create plans in Stripe
     *
     * @param array $plans
     */
    protected function createStripePlans($plans)
    {
        foreach ($plans as $plan) {
            if ($plan->id === 'free') {
                $this->line('Skipping free plan, since the "free" plan is handled by Spark internally.');
                continue;
            }

            if ($this->planExists($plan)) {
                $this->line('Stripe plan ' . $plan->id . ' already exists');
            } else {
                Stripe\Plan::create([
                    'id'                   => $plan->id,
                    'nickname'             => $plan->name,
                    'product'              => $this->getProductId(),
                    'amount'               => $plan->price * 100,
                    'interval'             => str_replace('ly', '', $plan->interval),
                    'currency'             => config('cashier.currency'),
                    'trial_period_days'    => $plan->trialDays,
                    'billing_scheme'       => 'per_unit',
                    'usage_type'           => Spark::noProrate() ? 'licensed' : 'metered',
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
