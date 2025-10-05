<template>
    <footer class="gwr-footer flex flex-col-reverse md:flex-row justify-between md:items-end gap-4">
        <AnnaLogo class="h-8 md:h-12" />
        <div class="gwr-footer__links flex flex-col md:flex-row gap-2">
            <NuxtLink to="https://annarosenwasser.ch/kontakt" target="_blank" class="underline w-fit">Kontakt</NuxtLink>
            <NuxtLink to="https://annarosenwasser.ch/impressum" target="_blank" class="underline w-fit">Impressum</NuxtLink>
            <NuxtLink to="https://annarosenwasser.ch/datenschutz" target="_blank" class="underline w-fit">Datenschutz</NuxtLink>
        </div>
    </footer>
</template>

<style lang="scss" scoped>
.gwr-footer {

    a {
        font-size: 0.6rem;
    }
}
</style>