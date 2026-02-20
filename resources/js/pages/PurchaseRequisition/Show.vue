<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Printer, FileText } from 'lucide-vue-next';

// Komponen UI
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';

import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

// ==========================================
// INTERFACE & PROPS
// ==========================================
interface MPRDetail {
    id: number;
    quantity: number;
    type: string;
    purpose: string;
    description: string | null;
    item: {
        id: number;
        name: string;
    };
}

interface MPR {
    id: number;
    request_date: string;
    mpr_no: string;
    name: string;
    code: string;
    location: string;
    created_at: string;
    details: MPRDetail[];
}

const props = defineProps<{
    mpr: MPR;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Purchase Requisitions', href: '/purchase-requisitions' },
    { title: 'Detail', href: '#' },
];

// Helper Format Tanggal
const formatDate = (dateString: string) => {
    const options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'long', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

// Fungsi Cetak Dokumen
const printDocument = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Detail MPR - ${mpr.mpr_no}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4 md:p-6 max-w-5xl">

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6 print:hidden">
                <Button variant="outline" as-child>
                    <Link href="/purchase-requisitions">
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Kembali ke Daftar
                    </Link>
                </Button>

                <Button @click="printDocument" class="bg-indigo-600 hover:bg-indigo-700 text-white">
                    <Printer class="w-4 h-4 mr-2" />
                    Cetak Dokumen
                </Button>
            </div>

            <Card class="print:border-0 print:shadow-none print:m-0 print:p-0">

                <CardHeader class="border-b bg-muted/20 print:bg-transparent print:border-b-2 print:border-black">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-primary/10 rounded-xl text-primary print:hidden">
                                <FileText class="w-8 h-8" />
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold tracking-tight uppercase print:text-black">Purchase Requisition</h1>
                                <p class="text-muted-foreground print:text-black">Dokumen Pengajuan Pembelian Barang</p>
                            </div>
                        </div>
                        <div class="text-left md:text-right">
                            <h2 class="text-xl font-bold text-primary print:text-black">{{ mpr.mpr_no }}</h2>
                            <p class="text-sm text-muted-foreground print:text-black">
                                Tanggal Request: <span class="font-medium text-foreground print:text-black">{{ formatDate(mpr.request_date) }}</span>
                            </p>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-muted-foreground print:text-black">Nama Pemohon</p>
                                <p class="font-semibold text-lg print:text-black">{{ mpr.name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground print:text-black">Kode Departemen/Proyek</p>
                                <p class="font-medium print:text-black">{{ mpr.code }}</p>
                            </div>
                        </div>

                        <div class="space-y-3 md:text-right">
                            <div>
                                <p class="text-sm text-muted-foreground print:text-black">Lokasi Penggunaan</p>
                                <p class="font-medium print:text-black">{{ mpr.location }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground print:text-black">Dibuat Pada Sistem</p>
                                <p class="font-medium print:text-black">{{ formatDate(mpr.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 border rounded-lg overflow-hidden print:border-black">
                        <Table class="print:text-black">
                            <TableHeader class="bg-muted/50 print:bg-transparent print:border-b-2 print:border-black">
                                <TableRow>
                                    <TableHead class="w-12 text-center print:text-black print:font-bold">No</TableHead>
                                    <TableHead class="print:text-black print:font-bold">Nama Barang</TableHead>
                                    <TableHead class="text-center w-24 print:text-black print:font-bold">Qty</TableHead>
                                    <TableHead class="w-32 text-center print:text-black print:font-bold">Jenis</TableHead>
                                    <TableHead class="print:text-black print:font-bold">Tujuan Penggunaan</TableHead>
                                    <TableHead class="print:text-black print:font-bold">Keterangan</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(detail, index) in mpr.details" :key="detail.id" class="print:border-b print:border-black/20">
                                    <TableCell class="text-center font-medium">{{ index + 1 }}</TableCell>
                                    <TableCell class="font-semibold">{{ detail.item.name }}</TableCell>
                                    <TableCell class="text-center font-bold text-lg">{{ detail.quantity }}</TableCell>
                                    <TableCell class="text-center">
                                        <Badge
                                            :variant="detail.type === 'equipment' ? 'default' : 'secondary'"
                                            class="print:border print:border-black print:bg-transparent print:text-black"
                                        >
                                            {{ detail.type === 'equipment' ? 'Equipment' : 'Consumable' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{{ detail.purpose || '-' }}</TableCell>
                                    <TableCell class="text-muted-foreground print:text-black">{{ detail.description || '-' }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div class="mt-20 grid grid-cols-3 gap-4 text-center hidden print:grid">
                        <div>
                            <p class="mb-16 text-sm">Dibuat Oleh,</p>
                            <div class="border-t border-black w-3/4 mx-auto pt-2">
                                <p class="font-bold">{{ mpr.name }}</p>
                                <p class="text-xs">Requester</p>
                            </div>
                        </div>
                        <div>
                            <p class="mb-16 text-sm">Diperiksa Oleh,</p>
                            <div class="border-t border-black w-3/4 mx-auto pt-2">
                                <p class="font-bold">...........................</p>
                                <p class="text-xs">Manager/SPV</p>
                            </div>
                        </div>
                        <div>
                            <p class="mb-16 text-sm">Disetujui Oleh,</p>
                            <div class="border-t border-black w-3/4 mx-auto pt-2">
                                <p class="font-bold">...........................</p>
                                <p class="text-xs">Procurement / Direktur</p>
                            </div>
                        </div>
                    </div>

                </CardContent>
            </Card>

        </div>
    </AppLayout>

</template>

<style scoped>
@media print {
    @page {
        margin: 1cm;
        size: A4 portrait;
    }
    body {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
}
</style>
