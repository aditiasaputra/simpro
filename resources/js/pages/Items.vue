<script setup lang="ts">
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { Loader2, Search, Edit2, X, ShoppingCart } from 'lucide-vue-next';
import { ref, onMounted, nextTick, watch } from 'vue';

// UI Components
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';
import { AlertDialog, AlertDialogContent, AlertDialogHeader, AlertDialogTitle, AlertDialogDescription, AlertDialogFooter, AlertDialogCancel, AlertDialogAction } from '@/components/ui/alert-dialog';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

// ==========================================
// INTERFACE & PROPS
// ==========================================
interface Item {
    id: number;
    name: string;
    type: string;
    description: string;
}

defineProps<{
    items: {
        data: Item[];
        next_page_url: string | null;
    },
    filters: { search?: string }
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'List Item', href: '/items' },
];

// ==========================================
// STATE MANAGEMENT
// ==========================================
// State Tabel & Pencarian
const localItems = ref<Item[]>([]);
const searchQuery = ref('');
const isLoadingMore = ref(false);
const observerTarget = ref<HTMLElement | null>(null);

// State Form Item (Create/Update)
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({
    name: '',
    type: 'equipment',
    description: '',
});

// State Toast Notifikasi
const showToast = ref(false);
const toastMessage = ref('');
const toastVariant = ref<'default' | 'destructive'>('default');

// State Modal Delete
const isDeleteDialogOpen = ref(false);
const itemToDelete = ref<number | null>(null);

// State Modal Keranjang (Cart)
const isCartDialogOpen = ref(false);
const selectedItemForCart = ref<Item | null>(null);
const cartForm = useForm({
    item_id: null as number | null,
    quantity: 1,
});

// ==========================================
// CORE FUNCTIONS
// ==========================================
const focusNameInput = async () => {
    await nextTick();
    document.getElementById('item-name')?.focus();
};

const triggerToast = (message: string, variant: 'default' | 'destructive' = 'default') => {
    toastMessage.value = message;
    toastVariant.value = variant;
    showToast.value = true;
    setTimeout(() => (showToast.value = false), 3500);
};

// 1. Pencarian (Debounce)
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchQuery, (value) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/items', { search: value }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['items'],
            onSuccess: (page) => {
                localItems.value = page.props.items.data;
            }
        });
    }, 300);
});

// 2. Infinite Scroll
const loadMore = (nextUrl: string | null) => {
    if (!nextUrl || isLoadingMore.value) return;
    isLoadingMore.value = true;
    router.get(nextUrl, { search: searchQuery.value }, {
        preserveState: true,
        preserveScroll: true,
        only: ['items'],
        onSuccess: (page) => {
            localItems.value.push(...page.props.items.data);
            isLoadingMore.value = false;
        }
    });
};

onMounted(() => {
    focusNameInput();
    localItems.value = usePage().props.items.data;

    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && !isLoadingMore.value) {
            const nextUrl = usePage().props.items.next_page_url;
            loadMore(nextUrl);
        }
    }, { rootMargin: '100px' });

    if (observerTarget.value) {
        observer.observe(observerTarget.value);
    }
});

// 3. Submit Form Item (Create & Update)
const submitItem = () => {
    if (isEditing.value && editingId.value) {
        form.put(`/items/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                const index = localItems.value.findIndex(i => i.id === editingId.value);
                if(index !== -1) {
                    localItems.value[index] = { ...localItems.value[index], name: form.name, type: form.type, description: form.description };
                }
                cancelEdit();
                triggerToast('Data berhasil diupdate! ✨', 'default');
            },
            onError: handleFormErrors
        });
    } else {
        form.post('/items', {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['items'], onSuccess: (page) => localItems.value = page.props.items.data });
                form.reset();
                triggerToast('Data berhasil disimpan! 🎉', 'default');
                focusNameInput();
            },
            onError: handleFormErrors
        });
    }
};

const handleFormErrors = (errors: any) => {
    for (const error in errors) {
        if (Object.hasOwnProperty.call(errors, error)) {
            triggerToast(errors[error], 'destructive');
        }
    }
    focusNameInput();
};

const editItem = (item: Item) => {
    isEditing.value = true;
    editingId.value = item.id;
    form.name = item.name;
    form.type = item.type;
    form.description = item.description;
    focusNameInput();
};

const cancelEdit = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    focusNameInput();
};

// 4. Delete Item
const confirmDelete = (id: number) => {
    itemToDelete.value = id;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (itemToDelete.value) {
        form.delete(`/items/${itemToDelete.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                localItems.value = localItems.value.filter(i => i.id !== itemToDelete.value);
                triggerToast('Data berhasil dihapus!', 'default');
                isDeleteDialogOpen.value = false;
                itemToDelete.value = null;
            },
        });
    }
};

// 5. Tambah ke Keranjang
const openCartDialog = (item: Item) => {
    selectedItemForCart.value = item;
    cartForm.item_id = item.id;
    cartForm.quantity = 1;
    isCartDialogOpen.value = true;
};

const submitCart = () => {
    cartForm.post('/cart-items', {
        preserveScroll: true,
        onSuccess: () => {
            isCartDialogOpen.value = false;
            triggerToast('Item berhasil ditambahkan ke keranjang! 🛒', 'default');
        },
        onError: () => {
            triggerToast('Gagal menambahkan ke keranjang.', 'destructive');
        }
    });
};
</script>

<template>
    <Head title="List Item" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div v-if="form.processing || isLoadingMore || cartForm.processing" class="fixed top-0 left-0 w-full h-1 bg-primary/20 z-50">
            <div class="h-full bg-primary animate-pulse w-full origin-left transition-all"></div>
        </div>

        <div
            v-if="showToast"
            class="fixed top-4 right-4 z-[100] p-4 rounded-md shadow-lg border min-w-[300px] transition-all duration-300"
            :class="toastVariant === 'destructive' ? 'bg-destructive text-destructive-foreground border-destructive' : 'bg-background text-foreground border-border'"
        >
            <p class="text-sm font-semibold">{{ toastMessage }}</p>
        </div>

        <div class="container mx-auto p-4 md:p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-1">
                    <Card class="sticky top-6">
                        <CardHeader>
                            <CardTitle class="flex items-center justify-between">
                                <span>{{ isEditing ? 'Edit Item' : 'Tambah Item' }}</span>
                                <Button v-if="isEditing" variant="ghost" size="icon" @click="cancelEdit" title="Batal Edit">
                                    <X class="w-4 h-4 text-muted-foreground" />
                                </Button>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="submitItem" class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="item-name">Nama Item <span class="text-destructive">*</span></Label>
                                    <Input
                                        id="item-name"
                                        v-model="form.name"
                                        placeholder="Contoh: Laptop"
                                        :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.name }"
                                    />
                                    <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                                </div>

                                <div class="space-y-2">
                                    <Label>Jenis Barang</Label>
                                    <Select v-model="form.type">
                                        <SelectTrigger class="w-full" :class="{ 'border-destructive focus:ring-destructive': form.errors.type }">
                                            <SelectValue placeholder="Pilih Jenis Barang..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="equipment">Equipment</SelectItem>
                                            <SelectItem value="consumable">Consumable</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.type" class="text-sm text-destructive">{{ form.errors.type }}</p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="item-desc">Deskripsi</Label>
                                    <Textarea
                                        id="item-desc"
                                        v-model="form.description"
                                        placeholder="Keterangan..."
                                        rows="3"
                                        :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.description }"
                                    />
                                    <p v-if="form.errors.description" class="text-sm text-destructive">{{ form.errors.description }}</p>
                                </div>

                                <div class="flex gap-2 mt-4">
                                    <Button
                                        v-if="isEditing"
                                        type="button"
                                        variant="outline"
                                        class="flex-1"
                                        @click="cancelEdit"
                                        :disabled="form.processing"
                                    >
                                        Batal
                                    </Button>

                                    <Button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="flex-1"
                                    >
                                        <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                                        {{ form.processing ? 'Memproses...' : (isEditing ? 'Update Data' : 'Simpan Data') }}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <div class="lg:col-span-2">
                    <Card class="flex flex-col h-[calc(100vh-140px)]">
                        <CardHeader class="border-b bg-muted/20 pb-4">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-lg">Daftar Item</CardTitle>
                                <div class="relative w-full md:w-72">
                                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                    <Input
                                        v-model="searchQuery"
                                        placeholder="Cari nama atau deskripsi..."
                                        class="pl-9 bg-background h-9"
                                    />
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="p-0 flex-1 overflow-y-auto relative">
                            <Table>
                                <TableHeader class="bg-muted/50 sticky top-0 z-10 shadow-sm">
                                    <TableRow>
                                        <TableHead class="w-12 text-center">No</TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Jenis</TableHead>
                                        <TableHead>Deskripsi</TableHead>
                                        <TableHead class="text-center w-36">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="(item, index) in localItems" :key="item.id">
                                        <TableCell class="text-center">{{ index + 1 }}</TableCell>
                                        <TableCell class="font-medium">{{ item.name }}</TableCell>
                                        <TableCell>
                                            <Badge :variant="item.type === 'equipment' ? 'default' : 'secondary'">
                                                {{ item.type === 'equipment' ? 'Equipment' : 'Consumable' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="max-w-[150px] truncate text-muted-foreground">
                                            {{ item.description || '-' }}
                                        </TableCell>
                                        <TableCell class="text-center space-x-1">
                                            <Button variant="ghost" size="icon" class="h-8 w-8 text-green-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20" @click="openCartDialog(item)" title="Tambah ke Keranjang">
                                                <ShoppingCart class="w-4 h-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" class="h-8 w-8 text-blue-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20" @click="editItem(item)" title="Edit">
                                                <Edit2 class="w-4 h-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" class="h-8 w-8 text-destructive hover:bg-destructive/10" @click="confirmDelete(item.id)" title="Hapus">
                                                <X class="w-4 h-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="localItems.length === 0">
                                        <TableCell colspan="5" class="h-32 text-center text-muted-foreground">
                                            {{ searchQuery ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data tersedia.' }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>

                            <div ref="observerTarget" class="h-12 w-full flex items-center justify-center">
                                <Loader2 v-if="isLoadingMore" class="w-5 h-5 animate-spin text-muted-foreground" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

            </div>
        </div>

        <AlertDialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Konfirmasi Hapus</AlertDialogTitle>
                    <AlertDialogDescription>
                        Apakah Anda yakin ingin menghapus <strong>{{ localItems.find(i => i.id === itemToDelete)?.name }}</strong>? Tindakan ini tidak dapat dibatalkan.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="isDeleteDialogOpen = false">Batal</AlertDialogCancel>
                    <AlertDialogAction @click="executeDelete" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                        Ya, Hapus Sekarang
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <Dialog :open="isCartDialogOpen" @update:open="isCartDialogOpen = $event">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Masukkan ke Keranjang</DialogTitle>
                    <DialogDescription>
                        Berapa banyak <strong>{{ selectedItemForCart?.name }}</strong> yang ingin Anda tambahkan?
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitCart" class="space-y-4 mt-2">
                    <div class="space-y-2">
                        <Label for="cart-qty">Jumlah (Quantity)</Label>
                        <Input
                            id="cart-qty"
                            type="number"
                            min="1"
                            v-model="cartForm.quantity"
                            required
                            class="text-lg"
                        />
                    </div>

                    <DialogFooter class="mt-6">
                        <Button type="button" variant="outline" @click="isCartDialogOpen = false">
                            Batal
                        </Button>
                        <Button type="submit" :disabled="cartForm.processing">
                            <Loader2 v-if="cartForm.processing" class="w-4 h-4 mr-2 animate-spin" />
                            Simpan ke Keranjang
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
