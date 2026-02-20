<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus, ClipboardList, Eye, Trash2 } from 'lucide-vue-next';

// Komponen UI
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';
import { AlertDialog, AlertDialogContent, AlertDialogHeader, AlertDialogTitle, AlertDialogDescription, AlertDialogFooter, AlertDialogCancel, AlertDialogAction } from '@/components/ui/alert-dialog';

import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

// Definisi Tipe Data
interface MPR {
    id: number;
    request_date: string;
    mpr_no: string;
    name: string;
    code: string;
    location: string;
}

const props = defineProps<{
    mprs: {
        data: MPR[];
        next_page_url: string | null;
        prev_page_url: string | null;
    }
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Purchase Requisitions', href: '/purchase-requisitions' },
];

// ==========================================
// STATE & LOGIKA TOAST NOTIFIKASI
// ==========================================
const showToast = ref(false);
const toastMessage = ref('');
const toastVariant = ref<'default' | 'destructive'>('default');

const triggerToast = (message: string, variant: 'default' | 'destructive' = 'default') => {
    toastMessage.value = message;
    toastVariant.value = variant;
    showToast.value = true;
    setTimeout(() => (showToast.value = false), 3500);
};

// Menangkap Flash Message dari Laravel
const page = usePage();
watch(
    () => page.props.flash?.success, // Asumsi flash message disimpan di props.flash.success
    (successMessage) => {
        if (successMessage) {
            triggerToast(successMessage as string, 'default');
        }
    },
    { immediate: true } // Jalankan langsung saat halaman pertama kali dimuat
);

// ==========================================
// STATE & LOGIKA HAPUS DATA
// ==========================================
const isDeleteDialogOpen = ref(false);
const itemToDelete = ref<number | null>(null);

const confirmDelete = (id: number) => {
    itemToDelete.value = id;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (itemToDelete.value) {
        router.delete(`/purchase-requisitions/${itemToDelete.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                isDeleteDialogOpen.value = false;
                itemToDelete.value = null;
                // Toast sukses hapus (jika controller tidak mengirim redirect flash)
                triggerToast('Dokumen MPR berhasil dihapus!', 'default');
            },
        });
    }
};

// Helper Format Tanggal
const formatDate = (dateString: string) => {
    const options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'long', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};
</script>

<template>
    <Head title="Daftar Purchase Requisition" />

    <AppLayout :breadcrumbs="breadcrumbs">

        <div
            v-if="showToast"
            class="fixed top-4 right-4 z-[100] p-4 rounded-md shadow-lg border min-w-[300px] transition-all duration-300"
            :class="toastVariant === 'destructive' ? 'bg-destructive text-destructive-foreground border-destructive' : 'bg-background text-foreground border-border'"
        >
            <p class="text-sm font-semibold">{{ toastMessage }}</p>
        </div>

        <div class="container mx-auto p-4 md:p-6 max-w-7xl">

            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-primary/10 rounded-full text-primary">
                        <ClipboardList class="w-6 h-6" />
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">Purchase Requisition (MPR)</h1>
                </div>

                <Button as-child size="lg">
                    <Link href="/purchase-requisitions/create">
                        <Plus class="w-5 h-5 mr-2" />
                        Buat MPR Baru
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader class="pb-4 border-b border-border/50">
                    <CardTitle class="text-lg">Riwayat Pengajuan</CardTitle>
                </CardHeader>
                <CardContent class="p-0 overflow-hidden">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="bg-muted/50">
                                <TableRow>
                                    <TableHead class="w-16 text-center">No</TableHead>
                                    <TableHead>Tanggal Request</TableHead>
                                    <TableHead>No MPR</TableHead>
                                    <TableHead>Requester</TableHead>
                                    <TableHead>Kode / Departemen</TableHead>
                                    <TableHead>Lokasi</TableHead>
                                    <TableHead class="text-center w-32">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow v-for="(mpr, index) in mprs.data" :key="mpr.id">
                                    <TableCell class="text-center">{{ index + 1 }}</TableCell>
                                    <TableCell class="font-medium whitespace-nowrap">{{ formatDate(mpr.request_date) }}</TableCell>
                                    <TableCell class="font-bold text-primary">{{ mpr.mpr_no }}</TableCell>
                                    <TableCell>{{ mpr.name }}</TableCell>
                                    <TableCell>{{ mpr.code }}</TableCell>
                                    <TableCell class="truncate max-w-[150px]">{{ mpr.location }}</TableCell>

                                    <TableCell>
                                        <div class="flex items-center justify-center gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="text-blue-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                                                as-child
                                                title="Lihat Detail"
                                            >
                                                <Link :href="`/purchase-requisitions/${mpr.id}`">
                                                    <Eye class="w-4 h-4" />
                                                </Link>
                                            </Button>

                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="text-destructive hover:bg-destructive/10"
                                                @click="confirmDelete(mpr.id)"
                                                title="Hapus MPR"
                                            >
                                                <Trash2 class="w-4 h-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="mprs.data.length === 0">
                                    <TableCell colspan="7" class="h-40 text-center text-muted-foreground">
                                        <ClipboardList class="w-10 h-10 mx-auto text-muted-foreground/50 mb-3" />
                                        <p>Belum ada data Purchase Requisition.</p>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div v-if="mprs.next_page_url || mprs.prev_page_url" class="flex items-center justify-between p-4 border-t">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="!mprs.prev_page_url"
                            @click="router.get(mprs.prev_page_url || '#')"
                        >
                            Sebelumnya
                        </Button>
                        <span class="text-sm text-muted-foreground">Menampilkan halaman berjalan</span>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="!mprs.next_page_url"
                            @click="router.get(mprs.next_page_url || '#')"
                        >
                            Selanjutnya
                        </Button>
                    </div>
                </CardContent>
            </Card>

        </div>

        <AlertDialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Dokumen MPR?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Apakah Anda yakin ingin menghapus dokumen Purchase Requisition ini beserta <strong>seluruh detail item</strong> di dalamnya? Tindakan ini permanen.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="isDeleteDialogOpen = false">Batal</AlertDialogCancel>
                    <AlertDialogAction @click="executeDelete" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                        Ya, Hapus Permanen
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

    </AppLayout>
</template>
