<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next'; // Icon loading standar shadcn
import { ref, onMounted, nextTick } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

import { AlertDialog, AlertDialogContent, AlertDialogHeader, AlertDialogTitle, AlertDialogDescription, AlertDialogFooter, AlertDialogCancel, AlertDialogAction } from '@/components/ui/alert-dialog';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';

interface Item {
    id: number;
    name: string;
    type: string;
    description: string;
}

defineProps<{ items: Item[] }>();

// State untuk Custom Toast Shadcn-style
const showToast = ref(false);
const toastMessage = ref('');
const toastVariant = ref<'default' | 'destructive'>('default');

// State untuk Delete Dialog
const isDeleteDialogOpen = ref(false);
const itemToDelete = ref<number | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'List Item', href: '/items' },
];

const form = useForm({
    name: '',
    type: 'equipment',
    description: '',
});

// Fokus menggunakan native DOM (Lebih stabil untuk komponen custom shadcn)
const focusNameInput = async () => {
    await nextTick();
    document.getElementById('item-name')?.focus();
};

onMounted(() => {
    focusNameInput();
});

const triggerToast = (message: string, variant: 'default' | 'destructive' = 'default') => {
    toastMessage.value = message;
    toastVariant.value = variant;
    showToast.value = true;
    setTimeout(() => (showToast.value = false), 3500);
};

const submit = () => {
    form.post('/items', {
        onSuccess: () => {
            form.reset();
            triggerToast('Data berhasil disimpan! 🎉', 'default');
            focusNameInput();
        },
        onError: (errors) => {
            for (const error in errors) {
                if (Object.hasOwnProperty.call(errors, error)) {
                    triggerToast(errors[error], 'destructive');
                }
            }
            focusNameInput();
        }
    });
};

const confirmDelete = (id: number) => {
    itemToDelete.value = id;
    isDeleteDialogOpen.value = true; // Buka modal shadcn
};

const executeDelete = () => {
    if (itemToDelete.value) {
        form.delete(`/items/${itemToDelete.value}`, {
            onSuccess: () => {
                triggerToast('Data berhasil dihapus!', 'default');
                isDeleteDialogOpen.value = false;
                itemToDelete.value = null;
                focusNameInput();
            },
        });
    }
};
</script>

<template>
    <Head title="List Item" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div v-if="form.processing" class="fixed top-0 left-0 w-full h-1 bg-primary/20 z-50">
            <div class="h-full bg-primary animate-pulse w-full origin-left transition-all"></div>
        </div>

        <div
            v-if="showToast"
            class="fixed top-4 right-4 z-[100] p-4 rounded-md shadow-lg border min-w-[300px] transition-all duration-300"
            :class="toastVariant === 'destructive' ? 'bg-destructive text-destructive-foreground border-destructive' : 'bg-background text-foreground border-border'"
        >
            <p class="text-sm font-semibold">{{ toastMessage }}</p>
        </div>

        <div class="container mx-auto p-2 md:p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-1">
                    <Card>
                        <CardHeader>
                            <CardTitle>Tambah Item</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="submit" class="space-y-4">
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

                                <Button type="submit" :disabled="form.processing" class="w-full mt-2">
                                    <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                                    {{ form.processing ? 'Memproses...' : 'Submit' }}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <div class="lg:col-span-2">
                    <Card>
                        <CardContent class="p-0 overflow-hidden">
                            <Table>
                                <TableHeader class="bg-muted/50">
                                    <TableRow>
                                        <TableHead class="w-12 text-center">No</TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Jenis</TableHead>
                                        <TableHead>Deskripsi</TableHead>
                                        <TableHead class="text-center w-24">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="(item, index) in items" :key="item.id">
                                        <TableCell class="text-center">{{ index + 1 }}</TableCell>
                                        <TableCell class="font-medium">{{ item.name }}</TableCell>
                                        <TableCell>
                                            <Badge :variant="item.type === 'equipment' ? 'default' : 'secondary'">
                                                {{ item.type === 'equipment' ? 'Equipment' : 'Consumable' }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="max-w-[200px] truncate text-muted-foreground">
                                            {{ item.description || '-' }}
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                @click="confirmDelete(item.id)"
                                            >
                                                Hapus
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="items.length === 0">
                                        <TableCell colspan="5" class="h-24 text-center text-muted-foreground">
                                            Belum ada data tersedia.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
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
                        Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan dan akan terhapus dari server.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="isDeleteDialogOpen = false">Batal</AlertDialogCancel>
                    <AlertDialogAction
                        @click="executeDelete"
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        Ya, Hapus Sekarang
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
