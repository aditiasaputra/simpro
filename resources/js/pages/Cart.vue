<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Trash2, Plus, Minus, ShoppingCart } from 'lucide-vue-next';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';
import { Checkbox } from '@/components/ui/checkbox';
import { AlertDialog, AlertDialogContent, AlertDialogHeader, AlertDialogTitle, AlertDialogDescription, AlertDialogFooter, AlertDialogCancel, AlertDialogAction } from '@/components/ui/alert-dialog';

import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface CartItem {
    id: number;
    quantity: number;
    item: {
        id: number;
        name: string;
        type: string;
        description: string;
    };
}

const props = defineProps<{
    cartItems: CartItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Keranjang', href: '/cart-items' },
];

// ==========================================
// STATE CHECKBOX
// ==========================================
const selectedItems = ref<Set<number>>(new Set());

const isAllSelected = computed(() =>
    props.cartItems.length > 0 && selectedItems.value.size === props.cartItems.length
);

const isIndeterminate = computed(() =>
    selectedItems.value.size > 0 && selectedItems.value.size < props.cartItems.length
);

const isItemSelected = (cartId: number) => selectedItems.value.has(cartId);

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedItems.value = new Set();
    } else {
        selectedItems.value = new Set(props.cartItems.map(c => c.id));
    }
};

const toggleItem = (cartId: number) => {
    const next = new Set(selectedItems.value);
    if (next.has(cartId)) {
        next.delete(cartId);
    } else {
        next.add(cartId);
    }
    selectedItems.value = next;
};

// ==========================================
// RINGKASAN
// ==========================================
const summaryTotalTypes = computed(() => selectedItems.value.size);

const summaryTotalPcs = computed(() =>
    props.cartItems
        .filter(cart => selectedItems.value.has(cart.id))
        .reduce((total, cart) => total + cart.quantity, 0)
);

// ==========================================
// HAPUS ITEM
// ==========================================
const isDeleteDialogOpen = ref(false);
const itemToDelete = ref<number | null>(null);

const confirmDelete = (cartItemId: number) => {
    itemToDelete.value = cartItemId;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (itemToDelete.value === null) return;

    router.delete(`/cart-items/${itemToDelete.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            const next = new Set(selectedItems.value);
            next.delete(itemToDelete.value!);
            selectedItems.value = next;
            isDeleteDialogOpen.value = false;
            itemToDelete.value = null;
        },
    });
};

// ==========================================
// UBAH QUANTITY
// ==========================================
const updateQuantity = (cartItemId: number, currentQty: number, change: number) => {
    const newQty = currentQty + change;

    if (newQty < 1) {
        confirmDelete(cartItemId);
        return;
    }

    router.put(`/cart-items/${cartItemId}`, { quantity: newQty }, {
        preserveScroll: true,
    });
};

// ==========================================
// LANJUTKAN PROSES (SIMPAN KE DB)
// ==========================================
const handleCheckout = () => {
    router.post('/cart-items/checkout', {
        cart_item_ids: Array.from(selectedItems.value),
    });
};
</script>

<template>
    <Head title="Keranjang" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4 md:p-6 max-w-7xl">

            <div class="flex items-center gap-3 mb-6">
                <div class="p-3 bg-primary/10 rounded-full text-primary">
                    <ShoppingCart class="w-6 h-6" />
                </div>
                <h1 class="text-2xl font-bold tracking-tight">Keranjang Barang</h1>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">

                <!-- Tabel Keranjang -->
                <div class="lg:col-span-2">
                    <Card>
                        <CardContent class="p-0 overflow-hidden">
                            <Table>
                                <TableHeader class="bg-muted/50">
                                    <TableRow>
                                        <TableHead class="w-12 text-center">
                                            <!--
                                                Gunakan native input sebagai fallback agar lebih reliable.
                                                Beri :data-state untuk trigger indeterminate pada shadcn Checkbox.
                                            -->
                                            <Checkbox
                                                :checked="isAllSelected"
                                                :data-state="isIndeterminate ? 'indeterminate' : isAllSelected ? 'checked' : 'unchecked'"
                                                aria-label="Pilih Semua"
                                                @click="toggleSelectAll"
                                            />
                                        </TableHead>
                                        <TableHead>Nama Barang</TableHead>
                                        <TableHead>Jenis</TableHead>
                                        <TableHead class="text-center w-40">Jumlah</TableHead>
                                        <TableHead class="text-center w-28">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow
                                        v-for="cart in cartItems"
                                        :key="cart.id"
                                        :class="{ 'bg-muted/20': isItemSelected(cart.id) }"
                                        class="cursor-pointer"
                                    >
                                        <TableCell class="text-center" @click.stop>
                                            <Checkbox
                                                :checked="isItemSelected(cart.id)"
                                                :data-state="isItemSelected(cart.id) ? 'checked' : 'unchecked'"
                                                aria-label="Pilih Item"
                                                @click="toggleItem(cart.id)"
                                            />
                                        </TableCell>

                                        <!-- Klik baris = toggle checkbox -->
                                        <TableCell
                                            class="font-medium"
                                            @click="toggleItem(cart.id)"
                                        >
                                            {{ cart.item.name }}
                                        </TableCell>

                                        <TableCell @click="toggleItem(cart.id)">
                                            <Badge :variant="cart.item.type === 'equipment' ? 'default' : 'secondary'">
                                                {{ cart.item.type === 'equipment' ? 'Equipment' : 'Consumable' }}
                                            </Badge>
                                        </TableCell>

                                        <TableCell @click.stop>
                                            <div class="flex items-center justify-center gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    class="w-8 h-8 rounded-full"
                                                    @click="updateQuantity(cart.id, cart.quantity, -1)"
                                                >
                                                    <Minus class="w-3 h-3" />
                                                </Button>

                                                <span class="w-8 text-center font-semibold">{{ cart.quantity }}</span>

                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    class="w-8 h-8 rounded-full"
                                                    @click="updateQuantity(cart.id, cart.quantity, 1)"
                                                >
                                                    <Plus class="w-3 h-3" />
                                                </Button>
                                            </div>
                                        </TableCell>

                                        <TableCell class="text-center" @click.stop>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-8 w-8 text-destructive hover:bg-destructive/10"
                                                @click="confirmDelete(cart.id)"
                                                title="Hapus dari Keranjang"
                                            >
                                                <Trash2 class="w-4 h-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="cartItems.length === 0">
                                        <TableCell colspan="5" class="h-40 text-center text-muted-foreground">
                                            <ShoppingCart class="w-10 h-10 mx-auto text-muted-foreground/50 mb-3" />
                                            <p>Keranjang Anda masih kosong.</p>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                <!-- Ringkasan -->
                <div class="lg:col-span-1" v-if="cartItems.length > 0">
                    <Card class="sticky top-6">
                        <CardHeader class="pb-4 border-b border-border/50">
                            <CardTitle class="text-lg">Ringkasan Keranjang</CardTitle>
                        </CardHeader>
                        <CardContent class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-muted-foreground">Macam Barang Dipilih:</span>
                                <span class="font-medium">{{ summaryTotalTypes }} Item</span>
                            </div>
                            <div class="flex justify-between items-center mb-6">
                                <span class="text-muted-foreground text-lg">Total Keseluruhan:</span>
                                <span
                                    class="font-bold text-2xl"
                                    :class="summaryTotalPcs > 0 ? 'text-primary' : 'text-muted-foreground'"
                                >
                                    {{ summaryTotalPcs }} Pcs
                                </span>
                            </div>
                            <Button
                                class="w-full text-md h-12"
                                size="lg"
                                :disabled="selectedItems.size === 0"
                                @click="handleCheckout"
                            >
                                Lanjutkan Proses ({{ summaryTotalTypes }})
                            </Button>
                        </CardContent>
                    </Card>
                </div>

            </div>
        </div>

        <!-- Modal Konfirmasi Hapus -->
        <AlertDialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus dari Keranjang?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Barang ini akan dihapus dari daftar keranjang Anda. Anda bisa menambahkannya kembali nanti melalui Master Data.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="isDeleteDialogOpen = false">Batal</AlertDialogCancel>
                    <AlertDialogAction
                        @click="executeDelete"
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        Ya, Hapus
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

    </AppLayout>
</template>
