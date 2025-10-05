<template>
  <NuxtLayout>
    <div class="gwr-container">
        <div class="gwr-heroine flex justify-between items-center">
            <NuxtLink to="/" class="h-20">
                <AppLogo class="h-20"/>
            </NuxtLink>
            <p class="italic text-end text-sm !leading-[1.1] opacity-75 hidden md:block">Eine Plattform von<br /><a href="https://annarosenwasser.ch" target="_blank" class="underline text-accent">Anna Rosenwasser.</a></p>
        </div>
    </div>
    <div class="gwr-container !pt-0">
        <NuxtPage />
    </div>
  </NuxtLayout>
</template>

<style scoped>
.page-enter-active,
.page-leave-active {
  transition: all 0.5s;
}
.page-enter-from,
.page-leave-to {
  opacity: 0;
}
</style>