<template>
  <div>
    <div>
      <div class="w-full max-w-screen-lg m-auto p-4 md:p-8">
        <router-link to="/cart">← Back to cart</router-link>
      </div>
    </div>
    <div class="w-full max-w-screen-lg m-auto p-4 md:p-8">
      <router-view :key="$route.path"></router-view>
    </div>
  </div>
</template>