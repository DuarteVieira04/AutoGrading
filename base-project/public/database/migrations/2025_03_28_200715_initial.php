<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('type', ['member', 'board', 'employee']);  // regular member, board member (adm), employee
            $table->boolean('blocked')->default(false);         // true if the user is blocked                        
            $table->enum('gender', ['M', 'F']);                 // Male, Female
            $table->string('photo')->nullable();
            $table->string('nif', 9)->nullable();                      // only for members
            $table->string('default_delivery_address')->nullable();    // only for members
            $table->enum('default_payment_type', ['Visa', 'PayPal', 'MB WAY'])->nullable();
            $table->string('default_payment_reference')->nullable();
            $table->json('custom')->nullable();
            $table->softDeletes();
        });

        Schema::create('cards', function (Blueprint $table) {
            // Note that the 'id' column is the same as the 'id' column in the 'users' table
            // This is a foreign key reference to the 'users' table
            // and it is also the primary key for the 'cards' table
            // This means that each card is associated with a user
            // Also, the 'id' column in the 'cards' table is not an autoincrementing column
            $table->bigInteger('id')->unsigned()->primary();
            $table->foreign('id')->references('id')->on('users');
            $table->integer('card_number')->unique();   // ranging from 100000 to 999999
            $table->decimal('balance', 9, 2)->default(0);
            $table->json('custom')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });


        Schema::create('settings', function (Blueprint $table) {
            // The 'id' column in the 'settings' table is not an autoincrementing column
            // because this table is meant to store a single row of settings
            $table->id();
            $table->decimal('membership_fee', 9, 2)->default(100);
            $table->json('custom')->nullable();
            $table->timestamps();
        });

        Schema::create('settings_shipping_costs', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_value_threshold', 9, 2);
            $table->decimal('max_value_threshold', 9, 2);
            $table->decimal('shipping_cost', 9, 2);
            $table->json('custom')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('image')->nullable();
            $table->json('custom')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('category_id')->unsigned();
            $table->foreign('category_id')->references('id')->on('categories');
            $table->string('name');
            $table->decimal('price', 9, 2);
            $table->integer('stock')->default(0);
            $table->string('description');
            $table->string('photo')->nullable();
            $table->integer('discount_min_qty')->nullable();
            $table->decimal('discount', 9, 2)->nullable();
            $table->integer('stock_lower_limit')->default(2);
            $table->integer('stock_upper_limit')->default(20);
            $table->json('custom')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });


        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('member_id')->unsigned();
            $table->foreign('member_id')->references('id')->on('users');
            $table->enum('status', ['pending', 'completed', 'canceled'])->default('pending');
            $table->date('date'); // stores the day only. Full timestamp is also available
            $table->decimal('total_items', 9, 2);   // Total (only for items in the order)
            $table->decimal('shipping_cost', 9, 2); // Shipping cost
            $table->decimal('total', 9, 2);         // Total (items + shipping cost)
            $table->string('nif', 9)->nullable();   
            $table->string('delivery_address');
            $table->string('pdf_receipt')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->json('custom')->nullable();
            $table->timestamps();
        });

        Schema::create('items_orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('order_id')->unsigned();
            $table->foreign('order_id')->references('id')->on('orders');
            $table->bigInteger('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products');
            $table->integer('quantity');
            $table->decimal('unit_price', 9, 2);
            $table->decimal('discount', 9, 2);
            $table->decimal('subtotal', 9, 2);
            $table->json('custom')->nullable();
        });

        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('card_id')->unsigned();
            $table->foreign('card_id')->references('id')->on('cards');
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('value', 9, 2);
            $table->date('date');    // stores the day only. Full timestamp is also available
            // Only for Debit Operations
            $table->enum('debit_type', ['order', 'membership_fee'])->nullable();
            // Only for credit operations:
            $table->enum('credit_type', ['payment', 'order_cancellation'])->nullable();
            $table->enum('payment_type', ['Visa', 'PayPal', 'MB WAY'])->nullable();
            $table->string('payment_reference')->nullable();
            //Usually for debit operations, but also possible for credit operations (order cancellation):
            $table->bigInteger('order_id')->unsigned()->nullable();
            $table->foreign('order_id')->references('id')->on('orders');
            $table->json('custom')->nullable();
            $table->timestamps();
        });

        Schema::create('supply_orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products');
            $table->bigInteger('registered_by_user_id')->unsigned();
            $table->foreign('registered_by_user_id')->references('id')->on('users');
            $table->enum('status', ['requested', 'completed'])->default('requested');  
            $table->integer('quantity');
            $table->json('custom')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products');
            $table->bigInteger('registered_by_user_id')->unsigned();
            $table->foreign('registered_by_user_id')->references('id')->on('users');
            $table->integer('quantity_changed');
            $table->json('custom')->nullable();
            $table->timestamps();
        });

        DB::statement("DROP VIEW IF EXISTS view_product_stock_logs");

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('        
                CREATE VIEW view_product_stock_logs AS
                SELECT 
                    "supply_order" AS log_type,
                    "S-" ||  supply_orders.id AS log_id,
                    supply_orders.product_id,
                    supply_orders.registered_by_user_id,
                    supply_orders.quantity AS quantity_changed,
                    supply_orders.status AS status,
                    supply_orders.custom,
                    supply_orders.created_at,
                    supply_orders.updated_at
                FROM supply_orders
                WHERE supply_orders.status = "completed"
                UNION
                SELECT 
                    "stock_adjustment" AS log_type,
                    "A-" ||  stock_adjustments.id AS log_id,
                    stock_adjustments.product_id,
                    stock_adjustments.registered_by_user_id,
                    stock_adjustments.quantity_changed,
                    "adjusted" AS status,
                    stock_adjustments.custom,
                    stock_adjustments.created_at,
                    stock_adjustments.updated_at
                FROM stock_adjustments
                UNION
                SELECT 
                    "order" AS log_type,
                    "O-" ||  items_orders.id AS log_id,
                    items_orders.product_id,
                    orders.member_id,
                    items_orders.quantity * -1,
                    orders.status AS status,
                    items_orders.custom,
                    orders.created_at,
                    orders.updated_at
                FROM items_orders INNER JOIN orders ON items_orders.order_id = orders.id
                WHERE orders.status = "completed"
                ORDER BY created_at DESC
            ');            
        } else {
            DB::statement('        
                CREATE VIEW view_product_stock_logs AS
                SELECT 
                    "supply_order" AS log_type,
                    CONCAT("S-", supply_orders.id) AS log_id,
                    supply_orders.product_id,
                    supply_orders.registered_by_user_id,
                    supply_orders.quantity AS quantity_changed,
                    supply_orders.status AS status,
                    supply_orders.custom,
                    supply_orders.created_at,
                    supply_orders.updated_at
                FROM supply_orders
                WHERE supply_orders.status = "completed"
                UNION
                SELECT 
                    "stock_adjustment" AS log_type,
                    CONCAT("A-", stock_adjustments.id) AS log_id,
                    stock_adjustments.product_id,
                    stock_adjustments.registered_by_user_id,
                    stock_adjustments.quantity_changed,
                    "adjusted" AS status,
                    stock_adjustments.custom,
                    stock_adjustments.created_at,
                    stock_adjustments.updated_at
                FROM stock_adjustments
                UNION
                SELECT 
                    "order" AS log_type,
                    CONCAT("O-", items_orders.id) AS log_id,
                    items_orders.product_id,
                    orders.member_id,
                    items_orders.quantity * -1,
                    orders.status AS status,
                    items_orders.custom,
                    orders.created_at,
                    orders.updated_at
                FROM items_orders INNER JOIN orders ON items_orders.order_id = orders.id
                WHERE orders.status = "completed"
                ORDER BY created_at DESC
            ');
        }
    }

    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
