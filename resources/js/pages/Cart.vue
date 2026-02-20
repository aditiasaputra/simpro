<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Trash2, Plus, Minus, ShoppingCart, Search, Loader2, X } from 'lucide-vue-next';

// Komponen UI
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/components/ui/card';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';
import { Checkbox } from '@/components/ui/checkbox';
import { AlertDialog, AlertDialogContent, AlertDialogHeader, AlertDialogTitle, AlertDialogDescription, AlertDialogFooter, AlertDialogCancel, AlertDialogAction } from '@/components/ui/alert-dialog';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';

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
// STATE CHECKBOX KERANJANG UTAMA
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
// RINGKASAN & CHECKOUT
// ==========================================
const summaryTotalTypes = computed(() => selectedItems.value.size);
const summaryTotalPcs = computed(() =>
    props.cartItems
        .filter(cart => selectedItems.value.has(cart.id))
        .reduce((total, cart) => total + cart.quantity, 0)
);

const handleCheckout = () => {
    router.post('/cart-items/checkout', {
        cart_item_ids: Array.from(selectedItems.value),
    });
};

// ==========================================
// HAPUS / UBAH QTY ITEM DI KERANJANG
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

const updateQuantity = (cartItemId: number, currentQty: number, change: number) => {
    const newQty = currentQty + change;
    if (newQty < 1) {
        confirmDelete(cartItemId);
        return;
    }
    router.put(`/cart-items/${cartItemId}`, { quantity: newQty }, { preserveScroll: true });
};

// ==========================================
// STATE MODAL "Tambah Item" (KIRI-KANAN)
// ==========================================
const isAddModalOpen = ref(false);

// State Kiri (Pencarian)
const searchKeyword = ref('');
const searchResults = ref<any[]>([]);
const isSearching = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

// State Kanan (Barang yang di-stage)
interface StagedItem {
    id: number;
    name: string;
    type: string;
    quantity: number;
}
const stagedItems = ref<StagedItem[]>([]);
const isSubmittingBulk = ref(false);

// Watcher untuk debounce AJAX Search
watch(searchKeyword, (query) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    if (!query || query.length < 2) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    searchTimeout = setTimeout(async () => {
        try {
            const response = await axios.get(`/items/search?q=${query}`);
            searchResults.value = response.data;
        } catch (error) {
            console.error(error);
        } finally {
            isSearching.value = false;
        }
    }, 300);
});

// Pindahkan dari kiri ke kanan
const stageItem = (item: any) => {
    const existingIndex = stagedItems.value.findIndex(i => i.id === item.id);
    if (existingIndex !== -1) {
        stagedItems.value[existingIndex].quantity += 1;
    } else {
        stagedItems.value.push({
            id: item.id,
            name: item.name,
            type: item.type,
            quantity: 1
        });
    }
};

const removeStagedItem = (index: number) => {
    stagedItems.value.splice(index, 1);
};

// Proses simpan ke database
const submitBulkItems = () => {
    if (stagedItems.value.length === 0) return;

    isSubmittingBulk.value = true;
    router.post('/cart-items/bulk', { items: stagedItems.value }, {
        preserveScroll: true,
        onSuccess: () => {
            isAddModalOpen.value = false;
            stagedItems.value = []; // Reset state
            searchKeyword.value = '';
            searchResults.value = [];
        },
        onFinish: () => {
            isSubmittingBulk.value = false;
        }
    });
};
</script>

<template>
    <Head title="Keranjang" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4 md:p-6 max-w-5xl">

            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-primary/10 rounded-full text-primary">
                        <ShoppingCart class="w-6 h-6" />
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">Keranjang Barang</h1>
                </div>

                <Button @click="isAddModalOpen = true" variant="outline" class="border-2 border-primary/50 text-primary hover:bg-primary/5">
                    <Plus class="w-4 h-4 mr-2" />
                    Tambah Item
                </Button>
            </div>

            <Card>
                <CardContent class="p-0 overflow-hidden">
                    <Table>
                        <TableHeader class="bg-muted/50">
                            <TableRow>
                                <TableHead class="w-12 text-center">
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
                                @click="toggleItem(cart.id)"
                            >
                                <TableCell class="text-center" @click.stop>
                                    <Checkbox
                                        :checked="isItemSelected(cart.id)"
                                        :data-state="isItemSelected(cart.id) ? 'checked' : 'unchecked'"
                                        aria-label="Pilih Item"
                                        @click="toggleItem(cart.id)"
                                    />
                                </TableCell>

                                <TableCell class="font-medium">
                                    {{ cart.item.name }}
                                </TableCell>

                                <TableCell>
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
                                    <Button @click="isAddModalOpen = true" variant="link" class="mt-2 text-primary">
                                        Cari & Tambah Item Sekarang
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>

                <CardFooter v-if="cartItems.length > 0" class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 md:p-6 bg-muted/10 border-t">
                    <div class="flex gap-6 w-full sm:w-auto text-sm sm:text-base text-muted-foreground">
                        <div class="flex flex-col">
                            <span>Barang Dipilih:</span>
                            <span class="font-semibold text-foreground text-lg">{{ summaryTotalTypes }} Item</span>
                        </div>
                        <div class="flex flex-col">
                            <span>Total Jumlah:</span>
                            <span class="font-bold text-primary text-lg">{{ summaryTotalPcs }} Pcs</span>
                        </div>
                    </div>

                    <Button
                        size="lg"
                        class="w-full sm:w-auto px-8"
                        :disabled="selectedItems.size === 0"
                        @click="handleCheckout"
                    >
                        Buat Form MPR ({{ summaryTotalTypes }})
                    </Button>
                </CardFooter>
            </Card>

        </div>

        <Dialog :open="isAddModalOpen" @update:open="isAddModalOpen = $event">
            <DialogContent class="sm:max-w-4xl p-0 overflow-hidden gap-0 flex flex-col max-h-[90vh]">

                <DialogHeader class="p-4 md:p-6 border-b bg-muted/20 shrink-0">
                    <DialogTitle>Cari & Tambah Item ke Keranjang</DialogTitle>
                    <DialogDescription>
                        Pilih barang dari kolom kiri, lalu atur jumlahnya di kolom kanan.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x h-[60vh] min-h-[400px] overflow-hidden">

                    <div class="flex flex-col h-full bg-background overflow-hidden">
                        <div class="p-4 border-b shrink-0">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                <Input
                                    v-model="searchKeyword"
                                    placeholder="Ketik nama barang..."
                                    class="pl-9 bg-muted/50"
                                />
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto p-2 min-h-0">
                            <div v-if="isSearching" class="flex justify-center p-8 text-muted-foreground">
                                <Loader2 class="w-6 h-6 animate-spin" />
                            </div>
                            <div v-else-if="searchKeyword.length > 0 && searchResults.length === 0" class="text-center p-8 text-muted-foreground text-sm">
                                Tidak ada barang yang cocok.
                            </div>
                            <div v-else-if="searchKeyword.length === 0" class="text-center p-8 text-muted-foreground text-sm flex flex-col items-center">
                                <Search class="w-8 h-8 mb-2 opacity-20" />
                                Mulai ketik untuk mencari.
                            </div>

                            <div v-else class="space-y-1 pb-4">
                                <div
                                    v-for="item in searchResults"
                                    :key="item.id"
                                    class="flex items-center justify-between p-3 rounded-md hover:bg-accent cursor-pointer group transition-colors"
                                    @click="stageItem(item)"
                                >
                                    <div>
                                        <p class="font-medium text-sm">{{ item.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ item.type === 'equipment' ? 'Equipment' : 'Consumable' }}</p>
                                    </div>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Plus class="w-4 h-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col h-full bg-muted/10 overflow-hidden">
                        <div class="p-4 border-b bg-background flex justify-between items-center shrink-0">
                            <h3 class="font-semibold text-sm">Barang Terpilih</h3>
                            <Badge variant="secondary">{{ stagedItems.length }}</Badge>
                        </div>

                        <div class="flex-1 overflow-y-auto p-4 min-h-0">
                            <div v-if="stagedItems.length === 0" class="h-full flex flex-col items-center justify-center text-muted-foreground text-sm opacity-50">
                                <ShoppingCart class="w-12 h-12 mb-3" />
                                Belum ada barang dipilih.
                            </div>

                            <div v-else class="space-y-3 pb-4">
                                <div
                                    v-for="(staged, index) in stagedItems"
                                    :key="index"
                                    class="flex items-start gap-3 bg-background p-3 rounded-lg border shadow-sm"
                                >
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm truncate">{{ staged.name }}</p>
                                        <div class="flex items-center gap-2 mt-2">
                                            <Label class="text-xs text-muted-foreground">Qty:</Label>
                                            <Input type="number" min="1" v-model="staged.quantity" class="h-8 w-20 text-sm" />
                                        </div>
                                    </div>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-destructive hover:bg-destructive/10 shrink-0" @click="removeStagedItem(index)">
                                        <X class="w-4 h-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <DialogFooter class="p-4 border-t bg-background shrink-0">
                    <Button variant="outline" @click="isAddModalOpen = false" :disabled="isSubmittingBulk">Batal</Button>
                    <Button @click="submitBulkItems" :disabled="stagedItems.length === 0 || isSubmittingBulk">
                        <Loader2 v-if="isSubmittingBulk" class="w-4 h-4 mr-2 animate-spin" />
                        Simpan ke Keranjang
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

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
