<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Moon, Sun } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const isDark = ref(false);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

onMounted(() => {
    // Deteksi otomatis apakah user sebelumnya memilih dark mode
    // atau jika sistem OS laptop/HP mereka menggunakan dark mode
    if (
        localStorage.theme === 'dark' ||
        (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
    ) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    }
});
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        @click="toggleTheme"
        title="Ganti Tema"
    >
        <Sun v-if="!isDark" class="w-5 h-5 text-yellow-500" />
        <Moon v-else class="w-5 h-5 text-slate-200" />
        <span class="sr-only">Toggle theme</span>
    </Button>
</template>
