<script setup lang="ts">
import TheSidebar from '@/components/TheSidebar.vue'
import TheNavbar from '@/components/TheNavbar.vue'
</script>

<template>
  <div class="app-layout">
    <TheSidebar />
    <div class="app-layout__body">
      <TheNavbar />
      <main class="app-layout__main">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.app-layout {
  display: flex;
  min-height: 100vh;
  background: var(--color-bg);

  &__body {
    flex: 1;
    margin-left: var(--sidebar-width);
    display: flex;
    flex-direction: column;
    min-height: 100vh;

    @media (max-width: 767px) {
      margin-left: 0;
    }
  }

  &__main {
    flex: 1;
    padding: var(--navbar-height) var(--space-6) var(--space-6);
    overflow-y: auto;

    @media (max-width: 767px) {
      padding: var(--navbar-height) var(--space-4) var(--space-4);
    }
  }
}
</style>
