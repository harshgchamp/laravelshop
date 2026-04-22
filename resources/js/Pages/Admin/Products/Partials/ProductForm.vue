<script setup>
/**
 * ProductForm.vue — Shared create/edit form for the Product resource
 *
 * Handles all product fields: title, slug, category, pricing, stock,
 * rich-text description (Quill editor), image upload, and published toggle.
 *
 * Same create/edit pattern as CategoryForm — prop-driven, single component.
 */
import { watch, computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

// PrimeVue components used in this form
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import InputSwitch from 'primevue/inputswitch';
import FileUpload from 'primevue/fileupload';
import Editor from 'primevue/editor'; // Quill-powered rich text editor
import InputNumber from 'primevue/inputnumber'; // numeric stepper with formatting
import InputGroup from 'primevue/inputgroup'; // label + input side-by-side
import Select from 'primevue/select'; // dropdown select (replaces Dropdown in PV4)

const toast = useToast();

const props = defineProps({
    product: { type: Object, default: null }, // null on Create, ProductResource on Edit
    categories: { type: Object, required: true }, // [{ id, name }] for the dropdown
    submitUrl: { type: String, required: true },
    method: { type: String, required: true }, // 'post' | 'put'
});

// ─── Inertia Form ────────────────────────────────────────────────────────────
/**
 * All keys in useForm() are serialised and sent to the server.
 * Only include fields that Laravel expects — no UI state here.
 *
 * _method: Laravel method spoofing for file uploads via POST.
 * slug: optional — Spatie HasSlug auto-generates it if left blank.
 */
const form = useForm({
    title: '',
    slug: '', // user can override; blank = auto-generated from title on server
    category_id: null,
    description: '',
    quantity: 0,
    in_stock: 0,
    price: 0,
    discount_price: null, // nullable — not all products have a discount
    image: null, // File object set by onImageSelect
    published: true,
    _method: props.method === 'put' ? 'put' : 'post',
});

// UI-only label — never sent to server (computed, not in useForm)
const methodText = computed(() => (props.method === 'put' ? 'Updated' : 'Created'));

// Preview URL: blob: URL for newly selected files, or full asset URL from ProductResource
const preview = ref(null);

// ─── Prefill on Edit ─────────────────────────────────────────────────────────
watch(
    () => props.product,
    (product) => {
        if (product) {
            form.title = product.title;
            form.slug = product.slug;
            form.category_id = product.category_id;
            form.description = product.description;
            form.quantity = product.quantity;
            form.in_stock = product.in_stock;
            form.price = product.price;
            form.discount_price = product.discount_price;
            form.published = Boolean(product.published);
            preview.value = product.image; // full URL from ProductResource
        }
    },
    { immediate: true },
);

// ─── Image Handler ───────────────────────────────────────────────────────────
const onImageSelect = (event) => {
    const file = event.files[0];
    form.image = file;
    preview.value = URL.createObjectURL(file); // blob: URL for immediate preview
};

// ─── Submit ──────────────────────────────────────────────────────────────────
const submit = () => {
    form.post(props.submitUrl, {
        forceFormData: true, // always send FormData even without a file (for consistency)
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: methodText.value,
                detail: `Product ${methodText.value.toLowerCase()} successfully.`,
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Validation Error',
                detail: 'Please check the form fields and try again.',
                life: 5000,
            });
        },
    });
};
</script>

<template>
    <div class="card bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <!-- Title — required, max 200 chars, drives slug auto-generation on the server -->
        <div class="mb-4">
            <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Title</label>
            <InputText v-model="form.title" class="w-full" placeholder="e.g. iPhone 15 Pro Max" />
            <small v-if="form.errors.title" class="text-red-500 mt-1 block">{{
                form.errors.title
            }}</small>
        </div>

        <!--
        Slug — optional, auto-generated from title by Spatie HasSlug if left blank.
        alpha_dash validation: only a-z, 0-9, hyphens, underscores allowed.
    -->
        <div class="mb-4">
            <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">
                Slug
                <span class="text-gray-400 text-sm font-normal">(auto-generated if blank)</span>
            </label>
            <InputText v-model="form.slug" class="w-full" placeholder="e.g. iphone-15-pro-max" />
            <small v-if="form.errors.slug" class="text-red-500 mt-1 block">{{
                form.errors.slug
            }}</small>
        </div>

        <!--
        Category dropdown.
        optionValue="id"   → form.category_id stores the integer PK, not the whole object.
        optionLabel="name" → the visible text in the dropdown options.
    -->
        <div class="mb-4">
            <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Category</label>
            <Select
                v-model="form.category_id"
                :options="categories"
                option-value="id"
                option-label="name"
                placeholder="Select a category"
                class="w-full"
            />
            <small v-if="form.errors.category_id" class="text-red-500 mt-1 block">{{
                form.errors.category_id
            }}</small>
        </div>

        <!--
        Numeric fields in a flex row.
        InputGroup combines a label button + InputNumber for a compact, clear layout.
        InputNumber prevents non-numeric input and handles locale formatting.
    -->
        <div class="flex gap-4 mb-4">
            <!-- Quantity: how many units are in stock overall -->
            <div class="flex-1">
                <InputGroup>
                    <Button label="Quantity" class="shrink-0" />
                    <InputNumber v-model="form.quantity" :min="0" />
                </InputGroup>
                <small v-if="form.errors.quantity" class="text-red-500 mt-1 block">{{
                    form.errors.quantity
                }}</small>
            </div>

            <!--
            In Stock: binary flag — 1 (in stock) or 0 (out of stock).
            Separate from quantity because a product can have quantity > 0 but
            be manually marked out of stock by the admin.
        -->
            <div class="flex-1">
                <InputGroup>
                    <Button label="In Stock" class="shrink-0" />
                    <InputNumber v-model="form.in_stock" :min="0" :max="1" />
                </InputGroup>
                <small v-if="form.errors.in_stock" class="text-red-500 mt-1 block">{{
                    form.errors.in_stock
                }}</small>
            </div>

            <!-- Price: base selling price in GBP, stored as decimal(10,2) -->
            <div class="flex-1">
                <InputGroup>
                    <Button label="£ Price" class="shrink-0" />
                    <InputNumber
                        v-model="form.price"
                        :min="0"
                        :min-fraction-digits="2"
                        :max-fraction-digits="2"
                    />
                </InputGroup>
                <small v-if="form.errors.price" class="text-red-500 mt-1 block">{{
                    form.errors.price
                }}</small>
            </div>

            <!-- Discount Price: optional reduced price shown alongside original -->
            <div class="flex-1">
                <InputGroup>
                    <Button label="£ Discount" class="shrink-0" />
                    <InputNumber
                        v-model="form.discount_price"
                        :min="0"
                        :min-fraction-digits="2"
                        :max-fraction-digits="2"
                    />
                </InputGroup>
                <small v-if="form.errors.discount_price" class="text-red-500 mt-1 block">{{
                    form.errors.discount_price
                }}</small>
            </div>
        </div>

        <!--
        Description — Quill rich text editor (PrimeVue Editor component).
        v-model binds to form.description which stores HTML string.
        v-html is used in ProductDetail.vue to render the stored HTML.
        SECURITY: output is server-stored admin input, not user input — XSS risk is minimal.
    -->
        <div class="mb-4">
            <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200"
                >Description</label
            >
            <Editor v-model="form.description" editor-style="height: 200px" class="w-full" />
        </div>

        <!-- Image upload — same pattern as CategoryForm -->
        <div class="mb-4">
            <label class="block mb-2 font-medium text-gray-700 dark:text-gray-200">Image</label>
            <FileUpload
                mode="basic"
                accept="image/*"
                :auto="false"
                choose-label="Choose Image"
                @select="onImageSelect"
            />
            <div v-if="preview" class="mt-3">
                <img
                    :src="preview"
                    class="h-32 rounded shadow object-cover"
                    alt="Product preview"
                />
            </div>
            <small v-if="form.errors.image" class="text-red-500 mt-1 block">{{
                form.errors.image
            }}</small>
        </div>

        <!-- Published toggle — controls storefront visibility without deleting the product -->
        <div class="mb-6 flex items-center gap-3">
            <InputSwitch v-model="form.published" />
            <span class="text-gray-700 dark:text-gray-200">Published (visible on storefront)</span>
        </div>

        <Button
            label="Save Product"
            icon="pi pi-check"
            :loading="form.processing"
            @click="submit"
        />
    </div>
</template>
