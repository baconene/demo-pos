<?php

namespace Database\Seeders;

/**
 * Fictional, client-neutral sample data for a new DEMO POS installation.
 * All stock, cost and selling-price figures are illustrative (PHP) only.
 * No user credentials, transactions or source-client business data live here.
 */
final class DemoCatalogSamples
{
    public const CATEGORIES = [
        ['name' => 'Burgers', 'slug' => 'burgers', 'display_order' => 1],
        ['name' => 'Rice Meals', 'slug' => 'rice-meals', 'display_order' => 2],
        ['name' => 'Snacks', 'slug' => 'snacks', 'display_order' => 3],
        ['name' => 'Hot Coffee', 'slug' => 'hot-coffee', 'display_order' => 4],
        ['name' => 'Cold Drinks', 'slug' => 'cold-drinks', 'display_order' => 5],
    ];

    // Quantities are training examples, not actual stock-in transactions.
    public const INGREDIENTS = [
        ['name' => 'Ground Beef', 'unit' => 'kg', 'current_quantity' => 8, 'min_quantity' => 2, 'cost_per_unit' => 380],
        ['name' => 'Chicken Fillet', 'unit' => 'kg', 'current_quantity' => 10, 'min_quantity' => 2, 'cost_per_unit' => 240],
        ['name' => 'Uncooked Rice', 'unit' => 'kg', 'current_quantity' => 20, 'min_quantity' => 4, 'cost_per_unit' => 55],
        ['name' => 'Potatoes', 'unit' => 'kg', 'current_quantity' => 10, 'min_quantity' => 2, 'cost_per_unit' => 105],
        ['name' => 'Coffee Beans', 'unit' => 'kg', 'current_quantity' => 4, 'min_quantity' => 1, 'cost_per_unit' => 900],
        ['name' => 'Fresh Milk', 'unit' => 'ltr', 'current_quantity' => 12, 'min_quantity' => 3, 'cost_per_unit' => 105],
        ['name' => 'Tea Leaves', 'unit' => 'kg', 'current_quantity' => 2, 'min_quantity' => 0.25, 'cost_per_unit' => 1100],
        ['name' => 'Sugar', 'unit' => 'kg', 'current_quantity' => 6, 'min_quantity' => 1, 'cost_per_unit' => 80],
        ['name' => 'Salt', 'unit' => 'kg', 'current_quantity' => 2, 'min_quantity' => 0.25, 'cost_per_unit' => 30],
        ['name' => 'Garlic', 'unit' => 'kg', 'current_quantity' => 2, 'min_quantity' => 0.25, 'cost_per_unit' => 180],
        ['name' => 'Cooking Oil', 'unit' => 'ltr', 'current_quantity' => 8, 'min_quantity' => 2, 'cost_per_unit' => 150],
        ['name' => 'Water', 'unit' => 'ltr', 'current_quantity' => 80, 'min_quantity' => 10, 'cost_per_unit' => 5],
        ['name' => 'Burger Bun', 'unit' => 'pcs', 'current_quantity' => 60, 'min_quantity' => 15, 'cost_per_unit' => 20],
        ['name' => 'Cheese Slice', 'unit' => 'pcs', 'current_quantity' => 80, 'min_quantity' => 15, 'cost_per_unit' => 10],
        ['name' => 'Lettuce', 'unit' => 'kg', 'current_quantity' => 3, 'min_quantity' => 0.5, 'cost_per_unit' => 160],
        ['name' => 'Tomato', 'unit' => 'kg', 'current_quantity' => 5, 'min_quantity' => 1, 'cost_per_unit' => 110],
        ['name' => 'Sandwich Bread', 'unit' => 'pcs', 'current_quantity' => 90, 'min_quantity' => 20, 'cost_per_unit' => 8],
        ['name' => 'Egg', 'unit' => 'pcs', 'current_quantity' => 80, 'min_quantity' => 20, 'cost_per_unit' => 9],
        ['name' => 'Butter', 'unit' => 'kg', 'current_quantity' => 2, 'min_quantity' => 0.5, 'cost_per_unit' => 360],
        ['name' => 'Ice', 'unit' => 'kg', 'current_quantity' => 20, 'min_quantity' => 4, 'cost_per_unit' => 25],
        ['name' => 'Chocolate Syrup', 'unit' => 'ltr', 'current_quantity' => 4, 'min_quantity' => 1, 'cost_per_unit' => 200],
        ['name' => 'Bottled Water 500 ml', 'unit' => 'pcs', 'current_quantity' => 100, 'min_quantity' => 20, 'cost_per_unit' => 14],
    ];

    // Prepared Food items are separate stock records, built from raw ingredients.
    public const FOODS = [
        ['name' => 'Demo Beef Patty', 'unit' => 'pcs', 'current_quantity' => 24, 'min_quantity' => 5, 'cost_per_unit' => 47],
        ['name' => 'Demo Grilled Chicken', 'unit' => 'pcs', 'current_quantity' => 24, 'min_quantity' => 5, 'cost_per_unit' => 37],
        ['name' => 'Demo Cooked Rice', 'unit' => 'pcs', 'current_quantity' => 40, 'min_quantity' => 10, 'cost_per_unit' => 7],
        ['name' => 'Demo French Fries', 'unit' => 'pcs', 'current_quantity' => 30, 'min_quantity' => 8, 'cost_per_unit' => 23],
        ['name' => 'Demo Espresso Shot', 'unit' => 'pcs', 'current_quantity' => 70, 'min_quantity' => 15, 'cost_per_unit' => 17],
        ['name' => 'Demo Egg Filling', 'unit' => 'pcs', 'current_quantity' => 25, 'min_quantity' => 5, 'cost_per_unit' => 13],
    ];

    // Key = Food name; value = [ingredient name => amount per Food serving].
    public const FOOD_COMPONENTS = [
        'Demo Beef Patty' => ['Ground Beef' => 0.120, 'Salt' => 0.003, 'Cooking Oil' => 0.010],
        'Demo Grilled Chicken' => ['Chicken Fillet' => 0.140, 'Garlic' => 0.007, 'Cooking Oil' => 0.010, 'Salt' => 0.003],
        'Demo Cooked Rice' => ['Uncooked Rice' => 0.100, 'Water' => 0.200],
        'Demo French Fries' => ['Potatoes' => 0.180, 'Cooking Oil' => 0.020, 'Salt' => 0.003],
        'Demo Espresso Shot' => ['Coffee Beans' => 0.018, 'Water' => 0.060],
        'Demo Egg Filling' => ['Egg' => 1, 'Butter' => 0.010, 'Salt' => 0.002],
    ];

    // Each product consumes prepared Foods and/or raw supplies per order.
    public const PRODUCTS = [
        ['sku' => 'DEMO-BURGER-01', 'category' => 'burgers', 'name' => 'Classic Cheeseburger', 'price' => 179, 'cost' => 81, 'ingredients' => ['Demo Beef Patty' => 1, 'Burger Bun' => 1, 'Cheese Slice' => 1, 'Lettuce' => 0.020, 'Tomato' => 0.020], 'modifiers' => ['Extra Cheese', 'Extra Sauce']],
        ['sku' => 'DEMO-MEAL-01', 'category' => 'rice-meals', 'name' => 'Grilled Chicken Rice Bowl', 'price' => 169, 'cost' => 55, 'ingredients' => ['Demo Grilled Chicken' => 1, 'Demo Cooked Rice' => 1], 'modifiers' => ['Extra Rice', 'Extra Sauce']],
        ['sku' => 'DEMO-MEAL-02', 'category' => 'rice-meals', 'name' => 'Extra Rice', 'price' => 35, 'cost' => 7, 'ingredients' => ['Demo Cooked Rice' => 1], 'modifiers' => []],
        ['sku' => 'DEMO-SNACK-01', 'category' => 'snacks', 'name' => 'Egg Sandwich', 'price' => 119, 'cost' => 29, 'ingredients' => ['Demo Egg Filling' => 1, 'Sandwich Bread' => 2], 'modifiers' => ['Extra Cheese']],
        ['sku' => 'DEMO-SNACK-02', 'category' => 'snacks', 'name' => 'French Fries', 'price' => 89, 'cost' => 23, 'ingredients' => ['Demo French Fries' => 1], 'modifiers' => ['Extra Sauce']],
        ['sku' => 'DEMO-COFFEE-01', 'category' => 'hot-coffee', 'name' => 'Hot Americano', 'price' => 95, 'cost' => 18, 'ingredients' => ['Demo Espresso Shot' => 1, 'Water' => 0.150], 'modifiers' => ['Extra Espresso Shot']],
        ['sku' => 'DEMO-DRINK-01', 'category' => 'cold-drinks', 'name' => 'Iced Latte', 'price' => 135, 'cost' => 39, 'ingredients' => ['Demo Espresso Shot' => 1, 'Fresh Milk' => 0.180, 'Ice' => 0.150], 'modifiers' => ['Extra Espresso Shot']],
        ['sku' => 'DEMO-DRINK-02', 'category' => 'cold-drinks', 'name' => 'Iced Chocolate Coffee', 'price' => 149, 'cost' => 44, 'ingredients' => ['Demo Espresso Shot' => 1, 'Fresh Milk' => 0.150, 'Ice' => 0.150, 'Chocolate Syrup' => 0.030], 'modifiers' => ['Extra Espresso Shot']],
        ['sku' => 'DEMO-DRINK-03', 'category' => 'cold-drinks', 'name' => 'Iced Tea', 'price' => 79, 'cost' => 18, 'ingredients' => ['Tea Leaves' => 0.005, 'Sugar' => 0.020, 'Water' => 0.300, 'Ice' => 0.100], 'modifiers' => []],
        ['sku' => 'DEMO-DRINK-04', 'category' => 'cold-drinks', 'name' => 'Bottled Water', 'price' => 35, 'cost' => 14, 'ingredients' => ['Bottled Water 500 ml' => 1], 'modifiers' => []],
    ];

    public const MODIFIERS = [
        ['name' => 'Extra Cheese', 'price' => 25],
        ['name' => 'Extra Sauce', 'price' => 15],
        ['name' => 'Extra Rice', 'price' => 35],
        ['name' => 'Extra Espresso Shot', 'price' => 35],
    ];

    public const SUPPLIERS = [
        ['name' => 'Sample Supplier — Food Ingredients'],
        ['name' => 'Sample Supplier — Packaging'],
    ];
}
