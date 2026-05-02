<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Create and authenticate an admin user. */
    private function adminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /** Create and authenticate a non-admin user. */
    private function regularUser(): User
    {
        return User::factory()->create();
    }

    // =========================================================================
    // AUTHENTICATION & AUTHORIZATION GUARDS
    // =========================================================================

    public function test_guests_are_redirected_to_login_on_index(): void
    {
        $response = $this->get(route('admin.categories.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_users_are_forbidden_from_index(): void
    {
        $response = $this->actingAs($this->regularUser())
            ->get(route('admin.categories.index'));

        $response->assertForbidden();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function test_admin_can_view_categories_index(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.categories.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Categories/Index')
                ->has('categories')
            );
    }

    public function test_index_returns_paginated_results(): void
    {
        Category::factory()->count(15)->create();

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.categories.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Categories/Index')
                ->has('categories.data', 10)   // default pagination: 10 per page
            );
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function test_admin_can_view_create_form(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.categories.create'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Categories/Create')
            );
    }

    public function test_guests_cannot_view_create_form(): void
    {
        $this->get(route('admin.categories.create'))
            ->assertRedirect(route('login'));
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function test_admin_can_create_a_category(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.categories.store'), [
                'name' => 'Electronics',
                'description' => 'All electronic items',
                'status' => true,
            ]);

        $response->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success', 'Category created successfully');

        $this->assertDatabaseHas('categories', [
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => true,
        ]);
    }

    public function test_store_auto_generates_slug_from_name(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('admin.categories.store'), [
                'name' => 'Home & Garden',
                'status' => true,
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Home & Garden',
            'slug' => 'home-garden',
        ]);
    }

    public function test_store_generates_unique_slug_when_duplicate_name(): void
    {
        Category::factory()->create(['name' => 'Books', 'slug' => 'books']);

        $this->actingAs($this->adminUser())
            ->post(route('admin.categories.store'), [
                'name' => 'Books',
                'status' => true,
            ]);

        $this->assertDatabaseHas('categories', ['slug' => 'books-1']);
    }

    public function test_store_uploads_image_to_storage(): void
    {
        Storage::fake('public');

        $this->actingAs($this->adminUser())
            ->post(route('admin.categories.store'), [
                'name' => 'Furniture',
                'status' => true,
                'image' => UploadedFile::fake()->image('chair.jpg'),
            ]);

        $category = Category::where('name', 'Furniture')->firstOrFail();
        $this->assertNotNull($category->image);
        Storage::disk('public')->assertExists($category->image);
    }

    public function test_store_fails_without_name(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.categories.store'), [
                'description' => 'No name supplied',
            ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseEmpty('categories');
    }

    public function test_store_fails_when_name_exceeds_255_characters(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.categories.store'), [
                'name' => str_repeat('A', 256),
                'status' => true,
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_store_fails_with_non_image_file(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.categories.store'), [
                'name' => 'Valid Name',
                'status' => true,
                'image' => UploadedFile::fake()->create('document.pdf', 500, 'application/pdf'),
            ]);

        $response->assertSessionHasErrors('image');
    }

    public function test_store_fails_when_image_exceeds_2mb(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.categories.store'), [
                'name' => 'Valid Name',
                'status' => true,
                'image' => UploadedFile::fake()->image('large.jpg')->size(2049),
            ]);

        $response->assertSessionHasErrors('image');
    }

    public function test_store_accepts_category_without_image_or_description(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.categories.store'), [
                'name' => 'Minimal Category',
                'status' => true,
            ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Minimal Category']);
    }

    // =========================================================================
    // EDIT
    // =========================================================================

    public function test_admin_can_view_edit_form(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.categories.edit', $category));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Categories/Edit')
                ->has('category')
                ->where('category.id', $category->id)
            );
    }

    public function test_edit_returns_404_for_nonexistent_category(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.categories.edit', 9999));

        $response->assertNotFound();
    }

    public function test_soft_deleted_category_is_not_accessible_for_edit(): void
    {
        $category = Category::factory()->create();
        $category->delete();

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.categories.edit', $category));

        $response->assertNotFound();
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function test_admin_can_update_a_category(): void
    {
        $category = Category::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

        $response = $this->actingAs($this->adminUser())
            ->put(route('admin.categories.update', $category), [
                'name' => 'New Name',
                'description' => 'Updated description',
                'status' => false,
            ]);

        $response->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success', 'Category updated successfully');

        $category->refresh();
        $this->assertSame('New Name', $category->name);
        $this->assertSame('new-name', $category->slug);
        $this->assertFalse((bool) $category->status);
    }

    public function test_update_slug_is_unique_excluding_self(): void
    {
        $catA = Category::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);
        $catB = Category::factory()->create(['name' => 'Beta',  'slug' => 'beta']);

        // Rename catA to same name as catA — slug should stay 'alpha', not 'alpha-1'
        $this->actingAs($this->adminUser())
            ->put(route('admin.categories.update', $catA), [
                'name' => 'Alpha',
                'status' => true,
            ]);

        $catA->refresh();
        $this->assertSame('alpha', $catA->slug);
    }

    public function test_update_replaces_image(): void
    {
        Storage::fake('public');

        $oldPath = 'categories/old-image.jpg';
        Storage::disk('public')->put($oldPath, 'fake content');
        $category = Category::factory()->create(['image' => $oldPath]);

        $this->actingAs($this->adminUser())
            ->put(route('admin.categories.update', $category), [
                'name' => $category->name,
                'status' => true,
                'image' => UploadedFile::fake()->image('new-image.jpg'),
            ]);

        Storage::disk('public')->assertMissing($oldPath);
        $category->refresh();
        Storage::disk('public')->assertExists($category->image);
    }

    public function test_update_keeps_existing_image_when_no_new_image_supplied(): void
    {
        Storage::fake('public');

        $oldPath = 'categories/existing.jpg';
        Storage::disk('public')->put($oldPath, 'fake content');
        $category = Category::factory()->create(['image' => $oldPath]);

        $this->actingAs($this->adminUser())
            ->put(route('admin.categories.update', $category), [
                'name' => $category->name,
                'status' => true,
            ]);

        $category->refresh();
        $this->assertSame($oldPath, $category->image);
    }

    public function test_update_fails_without_name(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->put(route('admin.categories.update', $category), [
                'description' => 'Missing name',
            ]);

        $response->assertSessionHasErrors('name');
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function test_admin_can_soft_delete_a_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success', 'Category deleted successfully');

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_soft_deleted_category_not_visible_in_index(): void
    {
        $active = Category::factory()->create(['name' => 'Visible']);
        $deleted = Category::factory()->create(['name' => 'Hidden']);
        $deleted->delete();

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.categories.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Categories/Index')
                ->where('categories.data', fn ($data) => collect($data)->contains('name', 'Visible') &&
                    ! collect($data)->contains('name', 'Hidden')
                )
            );
    }

    public function test_destroy_returns_404_for_nonexistent_category(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->delete(route('admin.categories.destroy', 9999));

        $response->assertNotFound();
    }

    public function test_guests_cannot_delete_a_category(): void
    {
        $category = Category::factory()->create();

        $this->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('login'));

        $this->assertNotSoftDeleted('categories', ['id' => $category->id]);
    }
}
