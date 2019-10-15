<?php

namespace Gilbitron\Laravel\Spark\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Cashier\Cashier;
use Laravel\Spark\Spark;
use Stripe;

class CreateStripeEndpoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spark:create-stripe-endpoints';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates endpoiints in Stripe based on the Spark app';

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

        $this->info('Creating endpoints ...');

        try {
            $this->createEndpoints();
        } catch (\Stripe\Error\InvalidRequest $e) {
            $this->error($e->getMessage());
        }

        $this->info('Finished');
    }

    /**
     * Try and create endpoints in Stripe
     *
     * @param array $plans
     */
    protected function createEndpoints()
    {
        $endpoint = \Stripe\WebhookEndpoint::create([
            'url' => config('app.url').'/webhook/stripe',
            'enabled_events' => [
                'customer.subscription.updated',
                'customer.subscription.deleted',
                'customer.updated',
                'customer.deleted',
                'invoice.payment_action_required',
                'invoice.payment_succeeded',
            ]
        ]);
    }
}
