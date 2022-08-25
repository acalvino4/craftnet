<template>
  <div class="w-full max-w-screen-lg m-auto p-4 md:p-8">
    <router-view :key="$route.path"></router-view>
  </div>
</template>