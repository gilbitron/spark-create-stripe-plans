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
     * Product Stripe IDs
     *
     * @var array
     */
    private $productStripeIds = [];
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

        $this->info('Fetch products...');
        $this->fetchProducts();

        $this->info('Creating user plans...');
        $this->createStripePlans(Spark::$plans);

        $this->info('Creating team plans...');
        $this->createStripePlans(Spark::$teamPlans);

        $this->info('Finished');
    }

    /**
     * Try and create product in Stripe
     *
     * @param array $plans
     */
    protected function fetchProducts()
    {
        try {
            /** @var \Stripe\Product[] $products */
            $products = Stripe\Product::all();
            foreach ($products as $product) {
                $this->productStripeIds[] = $product->id;
            }

            $this->info('Fetched products');
        } catch (\Stripe\Error\InvalidRequest | \Stripe\Exception\InvalidRequest $e) {
            $this->line('Unable to fetch products');
        }

    }

    /**
     * Create product in Stripe if needed and return the id
     *
     * @param array $plans
     */
    protected function getProductId($name = null)
    {
        $name = $name ?? Spark::$details['product'];

        $id = strtolower(str_replace(' ', '-', $name));

        $this->info('Creating looking up id for: '.$id);

        if (in_array($id, $this->productStripeIds)) {
            return $id;
        }

        $this->info('Creating product: '.$id);

        try {
            $product = Stripe\Product::create([
                'id'                   => $id,
                'name'                 => $name,
                'statement_descriptor' => Spark::$details['vendor'],
                'unit_label'           => Spark::$details['unit_label'] ?? null,
                'type'                 => 'service',
            ]);

            $this->productStripeIds[] = $product->id;

            $this->info('Stripe product created: ' . $id);
        } catch (\Stripe\Error\InvalidRequest | \Stripe\Exception\InvalidRequest $e) {
            $this->line('Stripe product ' . $id . ' already exists');
        }

        return $id;
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

            try {
                Stripe\Plan::create([
                    'id'                   => $plan->id,
                    'nickname'             => $plan->name,
                    'product'              => $this->getProductId($plan->attribute('product')),
                    'amount'               => $plan->price * 100,
                    'interval'             => str_replace('ly', '', $plan->interval),
                    'currency'             => config('cashier.currency'),
                    'trial_period_days'    => $plan->trialDays,
                    'billing_scheme'       => 'per_unit',
                    'usage_type'           => 'licensed',
                ]);

                $this->info('Stripe plan created: ' . $plan->id);
            } catch (\Stripe\Error\InvalidRequest | \Stripe\Exception\InvalidRequest $e) {
                $this->line('Stripe plan ' . $plan->id . ' already exists: '.$e->getMessage());
            }
        }
    }
}
