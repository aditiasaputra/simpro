<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Plus, Trash2, Loader2, Save, Search, Check, ChevronsUpDown } from 'lucide-vue-next';

// Komponen UI
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

const props = defineProps<{
    items: { id: number, name: string, type: string }[],
    initialDetails: any[],
    cartItemIds: number[]
}>();

const form = useForm({
    request_date: new Date().toISOString().split('T')[0],
    mpr_no: '',
    name: '',
    code: '',
    location: '',
    details: props.initialDetails,
    cart_item_ids: props.cartItemIds
});

// ==========================================
// STATE UNTUK NOTIFIKASI (TOAST)
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

// ==========================================
// STATE & LOGIKA AJAX COMBOBOX
// ==========================================
const openPopover = ref<{ [key: number]: boolean }>({});
const searchResults = ref<{ [key: number]: any[] }>({});
const isSearching = ref<{ [key: number]: boolean }>({});
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const onSearchInput = (query: string, index: number) => {
    if (searchTimeout) clearTimeout(searchTimeout);

    if (!query || query.length < 2) {
        searchResults.value[index] = [];
        return;
    }

    searchTimeout = setTimeout(async () => {
        isSearching.value[index] = true;
        try {
            const response = await axios.get(`/items/search?q=${query}`);
            searchResults.value[index] = response.data;
        } catch (error) {
            console.error("Gagal mencari barang", error);
        } finally {
            isSearching.value[index] = false;
        }
    }, 300);
};

const selectItem = (index: number, item: any) => {
    form.details[index].item_id = item.id;
    form.details[index].item_name = item.name;
    form.details[index].type = item.type;
    openPopover.value[index] = false;

    // Hapus error jika user sudah memilih item
    if(form.errors[`details.${index}.item_id`]) {
        delete form.errors[`details.${index}.item_id`];
    }
};

// ==========================================
// LOGIKA FORM DINAMIS
// ==========================================
const addDetail = () => {
    form.details.push({
        item_id: '',
        item_name: '',
        type: '',
        quantity: 1,
        purpose: '',
        description: ''
    });
};

const removeDetail = (index: number) => {
    if (form.details.length > 1) {
        form.details.splice(index, 1);
    }
};

const submit = () => {
    form.post('/purchase-requisitions', {
        preserveScroll: true,
        onSuccess: () => {
            // Jika berhasil, backend sudah diatur untuk redirect, tapi kita bisa beri log
            console.log('Validasi lolos dan sedang dialihkan...');
        },
        onError: (errors) => {
            // Tangkap error validasi dari Laravel (HTTP 200 dari Inertia)
            console.error('Validasi Gagal:', errors);
            triggerToast('Validasi gagal! Silakan periksa kembali isian Anda.', 'destructive');
        }
    });
};
</script>

<template>
    <Head title="Buat MPR Baru" />

    <AppLayout>
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

        <div class="container mx-auto p-4 md:p-6 max-w-7xl">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold tracking-tight">Buat Purchase Requisition (MPR)</h1>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <Card>
                    <CardHeader class="pb-4 border-b">
                        <CardTitle>Informasi Utama</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-6">
                        <div class="space-y-2">
                            <Label for="request_date">Tanggal Request <span class="text-destructive">*</span></Label>
                            <Input
                                id="request_date"
                                type="date"
                                v-model="form.request_date"
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.request_date }"
                            />
                            <p v-if="form.errors.request_date" class="text-xs text-destructive">{{ form.errors.request_date }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="mpr_no">No MPR <span class="text-destructive">*</span></Label>
                            <Input
                                id="mpr_no"
                                v-model="form.mpr_no"
                                placeholder="Contoh: MPR/2026/02/001"
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.mpr_no }"
                            />
                            <p v-if="form.errors.mpr_no" class="text-xs text-destructive">{{ form.errors.mpr_no }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="name">Nama Pemohon <span class="text-destructive">*</span></Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Nama pemohon..."
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="code">Kode <span class="text-destructive">*</span></Label>
                            <Input
                                id="code"
                                v-model="form.code"
                                placeholder="Kode departemen/proyek..."
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.code }"
                            />
                            <p v-if="form.errors.code" class="text-xs text-destructive">{{ form.errors.code }}</p>
                        </div>
                        <div class="space-y-2 lg:col-span-2">
                            <Label for="location">Lokasi <span class="text-destructive">*</span></Label>
                            <Input
                                id="location"
                                v-model="form.location"
                                placeholder="Lokasi pengiriman/penggunaan..."
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.location }"
                            />
                            <p v-if="form.errors.location" class="text-xs text-destructive">{{ form.errors.location }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-4 border-b flex flex-row items-center justify-between">
                        <CardTitle>Detail Item</CardTitle>
                        <Button type="button" size="sm" @click="addDetail">
                            <Plus class="w-4 h-4 mr-2" /> Tambah Baris
                        </Button>
                    </CardHeader>
                    <CardContent class="p-0 overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-muted/50 text-muted-foreground uppercase">
                                <tr>
                                    <th class="px-4 py-3 w-[300px]">Pilih Barang <span class="text-destructive">*</span></th>
                                    <th class="px-4 py-3 w-[150px] text-center">Type</th>
                                    <th class="px-4 py-3 w-[100px]">Qty <span class="text-destructive">*</span></th>
                                    <th class="px-4 py-3 w-[250px]">Tujuan</th>
                                    <th class="px-4 py-3 min-w-[200px]">Keterangan</th>
                                    <th class="px-4 py-3 w-[60px] text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(detail, index) in form.details" :key="index" class="border-b border-border/50">

                                    <td class="p-2 align-top">
                                        <Popover :open="openPopover[index]" @update:open="openPopover[index] = $event">
                                            <PopoverTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    role="combobox"
                                                    class="w-full justify-between font-normal"
                                                    :class="{ 'text-muted-foreground': !detail.item_id, 'border-destructive focus-visible:ring-destructive': form.errors[`details.${index}.item_id`] }"
                                                >
                                                    <span class="truncate">{{ detail.item_name || 'Cari barang...' }}</span>
                                                    <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                                </Button>
                                            </PopoverTrigger>

                                            <PopoverContent class="w-[300px] p-0" align="start">
                                                <div class="flex items-center border-b px-3">
                                                    <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
                                                    <input
                                                        class="flex h-10 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
                                                        placeholder="Ketik nama barang..."
                                                        @input="onSearchInput(($event.target as HTMLInputElement).value, index)"
                                                        autocomplete="off"
                                                    />
                                                </div>
                                                <div class="max-h-[250px] overflow-y-auto p-1">
                                                    <div v-if="isSearching[index]" class="py-6 text-center text-sm text-muted-foreground flex justify-center items-center">
                                                        <Loader2 class="w-4 h-4 mr-2 animate-spin" /> Mencari...
                                                    </div>
                                                    <div v-else-if="!searchResults[index] || searchResults[index].length === 0" class="py-6 text-center text-sm text-muted-foreground">
                                                        Ketik minimal 2 huruf.
                                                    </div>
                                                    <template v-else>
                                                        <div
                                                            v-for="item in searchResults[index]"
                                                            :key="item.id"
                                                            class="relative flex cursor-pointer select-none items-center rounded-sm px-2 py-2 text-sm outline-none hover:bg-accent hover:text-accent-foreground transition-colors"
                                                            @click="selectItem(index, item)"
                                                        >
                                                            <Check class="mr-2 h-4 w-4" :class="detail.item_id === item.id ? 'opacity-100 text-primary' : 'opacity-0'" />
                                                            <div class="flex flex-col">
                                                                <span class="font-medium">{{ item.name }}</span>
                                                                <span class="text-xs text-muted-foreground">{{ item.type === 'equipment' ? 'Equipment' : 'Consumable' }}</span>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </PopoverContent>
                                        </Popover>
                                        <p v-if="form.errors[`details.${index}.item_id`]" class="text-xs text-destructive mt-1">Barang wajib dipilih.</p>
                                    </td>

                                    <td class="p-2 align-top text-center pt-4">
                                        <Badge v-if="detail.type" :variant="detail.type === 'equipment' ? 'default' : 'secondary'">
                                            {{ detail.type === 'equipment' ? 'Equipment' : 'Consumable' }}
                                        </Badge>
                                        <span v-else class="text-muted-foreground text-xs italic">Otomatis</span>
                                    </td>

                                    <td class="p-2 align-top">
                                        <Input
                                            type="number"
                                            min="1"
                                            v-model="detail.quantity"
                                            :class="{ 'border-destructive focus-visible:ring-destructive': form.errors[`details.${index}.quantity`] }"
                                        />
                                        <p v-if="form.errors[`details.${index}.quantity`]" class="text-xs text-destructive mt-1">Isi Qty minimal 1.</p>
                                    </td>

                                    <td class="p-2 align-top">
                                        <Input
                                            v-model="detail.purpose"
                                            placeholder="Tujuan..."
                                            :class="{ 'border-destructive focus-visible:ring-destructive': form.errors[`details.${index}.purpose`] }"
                                        />
                                        <p v-if="form.errors[`details.${index}.purpose`]" class="text-xs text-destructive mt-1">Tujuan wajib diisi.</p>
                                    </td>

                                    <td class="p-2 align-top">
                                        <Textarea
                                            v-model="detail.description"
                                            placeholder="Catatan opsional..."
                                            rows="1"
                                            class="min-h-[40px]"
                                            :class="{ 'border-destructive focus-visible:ring-destructive': form.errors[`details.${index}.description`] }"
                                        />
                                        <p v-if="form.errors[`details.${index}.description`]" class="text-xs text-destructive mt-1">{{ form.errors[`details.${index}.description`] }}</p>
                                    </td>

                                    <td class="p-2 align-top text-center pt-3">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="text-destructive hover:bg-destructive/10 h-8 w-8"
                                            @click="removeDetail(index)"
                                            :disabled="form.details.length === 1"
                                            title="Hapus Baris"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <div class="flex justify-end gap-4">
                    <Button type="button" variant="outline" @click="$inertia.visit('/purchase-requisitions')">
                        Batal
                    </Button>
                    <Button type="submit" size="lg" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="w-5 h-5 mr-2 animate-spin" />
                        <Save v-else class="w-5 h-5 mr-2" />
                        Simpan Purchase Requisition
                    </Button>
                </div>
            </form>

        </div>
    </AppLayout>
</template>
