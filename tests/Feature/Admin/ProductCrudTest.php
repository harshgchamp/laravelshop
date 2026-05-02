<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function adminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function regularUser(): User
    {
        return User::factory()->create();
    }

    /** Minimal valid payload for creating a product. */
    private function validPayload(?int $categoryId = null): array
    {
        $categoryId ??= Category::factory()->create()->id;

        return [
            'title' => 'Test Product',
            'quantity' => 10,
            'price' => 99.99,
            'in_stock' => 1,
            'category_id' => $categoryId,
        ];
    }

    // =========================================================================
    // AUTHENTICATION & AUTHORIZATION GUARDS
    // =========================================================================

    public function test_guests_are_redirected_to_login_on_index(): void
    {
        $this->get(route('admin.products.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_users_are_forbidden_from_index(): void
    {
        $this->actingAs($this->regularUser())
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_guests_cannot_view_create_form(): void
    {
        $this->get(route('admin.products.create'))
            ->assertRedirect(route('login'));
    }

    public function test_guests_cannot_store_a_product(): void
    {
        $this->post(route('admin.products.store'), $this->validPayload())
            ->assertRedirect(route('login'));

        $this->assertDatabaseEmpty('products');
    }

    public function test_guests_cannot_delete_a_product(): void
    {
        $product = Product::factory()->create();

        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function test_admin_can_view_products_index(): void
    {
        Product::factory()->count(3)->create();

        $this->actingAs($this->adminUser())
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Products/Index')
                ->has('products')
            );
    }

    public function test_index_returns_paginated_results(): void
    {
        Product::factory()->count(15)->create();

        $this->actingAs($this->adminUser())
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Products/Index')
                ->has('products.data', 10)
            );
    }

    public function test_index_eager_loads_category(): void
    {
        $category = Category::factory()->create(['name' => 'Gadgets']);
        Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($this->adminUser())
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Products/Index')
                ->has('products.data', 1)
            );
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function test_admin_can_view_create_form(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Products/Create')
                ->has('categories')
            );
    }

    public function test_create_form_passes_categories_list(): void
    {
        Category::factory()->count(4)->create();

        $this->actingAs($this->adminUser())
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('categories', 4)
            );
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function test_admin_can_create_a_product(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), [
                'title' => 'Wireless Mouse',
                'quantity' => 50,
                'price' => 29.99,
                'in_stock' => 1,
                'category_id' => $category->id,
            ]);

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Product created successfully');

        $this->assertDatabaseHas('products', [
            'title' => 'Wireless Mouse',
            'price' => 29.99,
            'category_id' => $category->id,
        ]);
    }

    public function test_store_auto_generates_slug_from_title(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), array_merge($this->validPayload(), [
                'title' => 'Gaming Keyboard',
            ]));

        $this->assertDatabaseHas('products', ['slug' => 'gaming-keyboard']);
    }

    public function test_store_uses_provided_slug_when_given(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), array_merge($this->validPayload(), [
                'slug' => 'custom-slug',
            ]));

        $this->assertDatabaseHas('products', ['slug' => 'custom-slug']);
    }

    public function test_store_sets_created_by_to_authenticated_user(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->validPayload());

        $this->assertDatabaseHas('products', ['created_by' => $admin->id]);
    }

    public function test_store_uploads_image_to_storage(): void
    {
        Storage::fake('public');

        $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), array_merge($this->validPayload(), [
                'image' => UploadedFile::fake()->image('product.jpg'),
            ]));

        $product = Product::first();
        $this->assertNotNull($product->image);
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_store_stores_image_under_products_directory(): void
    {
        Storage::fake('public');

        $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), array_merge($this->validPayload(), [
                'image' => UploadedFile::fake()->image('widget.png'),
            ]));

        $product = Product::first();
        $this->assertStringStartsWith('products/', $product->image);
    }

    public function test_store_accepts_product_without_optional_fields(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), $this->validPayload());

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseCount('products', 1);
    }

    public function test_store_saves_discount_price_when_provided(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), array_merge($this->validPayload(), [
                'discount_price' => 19.99,
            ]));

        $this->assertDatabaseHas('products', ['discount_price' => 19.99]);
    }

    public function test_store_fails_without_title(): void
    {
        $payload = $this->validPayload();
        unset($payload['title']);

        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), $payload);

        $response->assertSessionHasErrors('title');
        $this->assertDatabaseEmpty('products');
    }

    public function test_store_fails_when_title_exceeds_200_characters(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), array_merge($this->validPayload(), [
                'title' => str_repeat('A', 201),
            ]));

        $response->assertSessionHasErrors('title');
    }

    public function test_store_fails_without_price(): void
    {
        $payload = $this->validPayload();
        unset($payload['price']);

        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), $payload);

        $response->assertSessionHasErrors('price');
    }

    public function test_store_fails_when_price_is_zero_or_negative(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), array_merge($this->validPayload(), [
                'price' => 0,
            ]));

        $response->assertSessionHasErrors('price');
    }

    public function test_store_fails_without_quantity(): void
    {
        $payload = $this->validPayload();
        unset($payload['quantity']);

        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), $payload);

        $response->assertSessionHasErrors('quantity');
    }

    public function test_store_fails_when_quantity_is_less_than_one(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), array_merge($this->validPayload(), [
                'quantity' => 0,
            ]));

        $response->assertSessionHasErrors('quantity');
    }

    public function test_store_fails_without_category_id(): void
    {
        $payload = $this->validPayload();
        unset($payload['category_id']);

        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), $payload);

        $response->assertSessionHasErrors('category_id');
    }

    public function test_store_fails_with_nonexistent_category_id(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), array_merge($this->validPayload(), [
                'category_id' => 9999,
            ]));

        $response->assertSessionHasErrors('category_id');
    }

    public function test_store_fails_with_duplicate_slug(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['slug' => 'existing-slug', 'category_id' => $category->id]);

        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), array_merge($this->validPayload($category->id), [
                'slug' => 'existing-slug',
            ]));

        $response->assertSessionHasErrors('slug');
        $this->assertDatabaseCount('products', 1);
    }

    public function test_store_fails_with_non_image_file(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), array_merge($this->validPayload(), [
                'image' => UploadedFile::fake()->create('document.pdf', 500, 'application/pdf'),
            ]));

        $response->assertSessionHasErrors('image');
    }

    public function test_store_fails_when_image_exceeds_2mb(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.products.store'), array_merge($this->validPayload(), [
                'image' => UploadedFile::fake()->image('large.jpg')->size(2049),
            ]));

        $response->assertSessionHasErrors('image');
    }

    // =========================================================================
    // EDIT
    // =========================================================================

    public function test_admin_can_view_edit_form(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->adminUser())
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Products/Edit')
                ->has('product')
                ->where('product.id', $product->id)
                ->has('categories')
            );
    }

    public function test_edit_returns_404_for_nonexistent_product(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('admin.products.edit', 9999))
            ->assertNotFound();
    }

    public function test_guests_cannot_view_edit_form(): void
    {
        $product = Product::factory()->create();

        $this->get(route('admin.products.edit', $product))
            ->assertRedirect(route('login'));
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function test_admin_can_update_a_product(): void
    {
        $product = Product::factory()->create(['title' => 'Old Title', 'price' => 10.00]);
        $category = Category::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->put(route('admin.products.update', $product), [
                'title' => 'New Title',
                'quantity' => 5,
                'price' => 49.99,
                'in_stock' => 0,
                'category_id' => $category->id,
            ]);

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Product updated successfully');

        $product->refresh();
        $this->assertSame('New Title', $product->title);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'price' => 49.99]);
    }

    public function test_update_replaces_image_and_deletes_old_one(): void
    {
        Storage::fake('public');

        $oldPath = 'products/old-image.jpg';
        Storage::disk('public')->put($oldPath, 'fake content');
        $product = Product::factory()->create(['image' => $oldPath]);

        $this->actingAs($this->adminUser())
            ->put(route('admin.products.update', $product), [
                'title' => $product->title,
                'quantity' => $product->quantity,
                'price' => $product->price,
                'in_stock' => $product->in_stock,
                'category_id' => $product->category_id,
                'image' => UploadedFile::fake()->image('new-image.jpg'),
            ]);

        Storage::disk('public')->assertMissing($oldPath);
        $product->refresh();
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_update_keeps_existing_image_when_no_new_image_supplied(): void
    {
        Storage::fake('public');

        $oldPath = 'products/existing.jpg';
        Storage::disk('public')->put($oldPath, 'fake content');
        $product = Product::factory()->create(['image' => $oldPath]);

        $this->actingAs($this->adminUser())
            ->put(route('admin.products.update', $product), [
                'title' => $product->title,
                'quantity' => $product->quantity,
                'price' => $product->price,
                'in_stock' => $product->in_stock,
                'category_id' => $product->category_id,
            ]);

        $product->refresh();
        $this->assertSame($oldPath, $product->image);
    }

    public function test_update_slug_is_unique_excluding_self(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['slug' => 'my-product', 'category_id' => $category->id]);

        $this->actingAs($this->adminUser())
            ->put(route('admin.products.update', $product), [
                'title' => $product->title,
                'slug' => 'my-product',
                'quantity' => $product->quantity,
                'price' => $product->price,
                'in_stock' => $product->in_stock,
                'category_id' => $category->id,
            ]);

        $product->refresh();
        $this->assertSame('my-product', $product->slug);
    }

    public function test_update_fails_without_title(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->put(route('admin.products.update', $product), [
                'quantity' => 5,
                'price' => 10.00,
                'in_stock' => 1,
                'category_id' => $product->category_id,
            ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_update_fails_without_price(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->put(route('admin.products.update', $product), [
                'title' => 'Valid Title',
                'quantity' => 5,
                'in_stock' => 1,
                'category_id' => $product->category_id,
            ]);

        $response->assertSessionHasErrors('price');
    }

    public function test_update_fails_with_duplicate_slug_from_another_product(): void
    {
        $category = Category::factory()->create();
        $productA = Product::factory()->create(['slug' => 'product-a', 'category_id' => $category->id]);
        $productB = Product::factory()->create(['slug' => 'product-b', 'category_id' => $category->id]);

        $response = $this->actingAs($this->adminUser())
            ->put(route('admin.products.update', $productB), [
                'title' => $productB->title,
                'slug' => 'product-a',
                'quantity' => $productB->quantity,
                'price' => $productB->price,
                'in_stock' => $productB->in_stock,
                'category_id' => $category->id,
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_update_returns_404_for_nonexistent_product(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->put(route('admin.products.update', 9999), [
                'title' => 'Ghost',
                'quantity' => 1,
                'price' => 10.00,
                'in_stock' => 1,
                'category_id' => Category::factory()->create()->id,
            ]);

        $response->assertNotFound();
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function test_admin_can_delete_a_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Product deleted successfully');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_destroy_deletes_associated_image_from_storage(): void
    {
        Storage::fake('public');

        $imagePath = 'products/to-delete.jpg';
        Storage::disk('public')->put($imagePath, 'content');
        $product = Product::factory()->create(['image' => $imagePath]);

        $this->actingAs($this->adminUser())
            ->delete(route('admin.products.destroy', $product));

        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_destroy_returns_404_for_nonexistent_product(): void
    {
        $this->actingAs($this->adminUser())
            ->delete(route('admin.products.destroy', 9999))
            ->assertNotFound();
    }

    public function test_non_admin_cannot_delete_a_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->regularUser())
            ->delete(route('admin.products.destroy', $product))
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_deleted_product_is_not_visible_in_index(): void
    {
        $visible = Product::factory()->create(['title' => 'Visible Product']);
        $hidden = Product::factory()->create(['title' => 'Hidden Product']);
        $hidden->delete();

        $this->actingAs($this->adminUser())
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Products/Index')
                ->where('products.data', fn ($data) => collect($data)->contains('title', 'Visible Product') &&
                    ! collect($data)->contains('title', 'Hidden Product')
                )
            );
    }
}
