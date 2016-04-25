# Spark Create Stripe Plans
A simple Laravel artisan command to create Spark plans in Stripe.

## Install

Require the library by running:

```
composer require gilbitron/spark-create-stripe-plans
```

Next you need to add the following to your `providers` array in `config/app.php`:

```
Gilbitron\Laravel\Spark\CreateStripePlansServiceProvider::class
```

## Usage

Make sure your details and plans are set up in your `SparkServiceProvider` then run:

```
php artisan spark:create-stripe-plans
```

Your plans should now be available in your [Stripe dashboard](https://dashboard.stripe.com). If you
re-run the command it will not overwrite existing plans.

## Credits

Spark Create Stripe Plans was created by [Gilbert Pellegrom](https://gilbert.pellegrom.me) from
[Dev7studios](https://dev7studios.com). Released under the MIT license.


