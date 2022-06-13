<template>
  <div
    v-show="tablePagination && tablePagination.last_page > 1"
    class="vuetable-pagination"
    :class="css.wrapperClass">
    <div>
      <btn
        @click="loadPage(1)"
        :class="['btn-nav', css.linkClass, isOnFirstPage ? css.disabledClass : '']"
        :disabled="isOnFirstPage">
        <icon icon="angle-double-left"></icon>
      </btn>
      <btn
        @click="loadPage('prev')"
        :class="['btn-nav', css.linkClass, isOnFirstPage ? css.disabledClass : '']"
        :disabled="isOnFirstPage">
        <icon icon="angle-left"></icon>
      </btn>
      <template v-if="notEnoughPages">
        <template v-for="n in totalPage">
          <btn
            :class="[css.pageClass, isCurrentPage(n) ? css.activeClass : '']"
            :key="'vuetable-page-' + n"
            v-html="n"
            @click="loadPage(n)"

          ></btn>
        </template>
      </template>
      <template v-else>
        <template v-for="n in windowSize">
          <btn
            :class="[css.pageClass, isCurrentPage(windowStart+n-1) ? css.activeClass : '']"
            :key="'vuetable-page-' + n"
            v-html="windowStart+n-1"
            @click="loadPage(windowStart+n-1)"
          ></btn>
        </template>
      </template>
      <btn
        @click="loadPage('next')"
        :class="['btn-nav', css.linkClass, isOnLastPage ? css.disabledClass : '']"
        :disabled="isOnLastPage">
        <icon icon="angle-right"></icon>
      </btn>
      <btn
        @click="loadPage(totalPage)"
        :class="['btn-nav', css.linkClass, isOnLastPage ? css.disabledClass : '']"
        :disabled="isOnLastPage">
        <icon icon="angle-double-right"></icon>
      </btn>
    </div>
  </div>
</template>

<script>
import PaginationMixin from 'vuetable-2/src/components/VuetablePaginationMixin.vue'

export default {
  mixins: [PaginationMixin],

  props: {
    css: {
      type: Object,
      default() {
        return {
          wrapperClass: 'ui right floated pagination menu text-center pt-6',
          activeClass: 'active',
          disabledClass: 'disabled',
          pageClass: 'item',
          linkClass: 'icon item',
          paginationClass: 'ui bottom attached segment grid',
          paginationInfoClass: 'left floated left aligned six wide column',
          dropdownClass: 'ui search dropdown',
          icons: {
            first: 'angle double left icon',
            prev: 'left chevron icon',
            next: 'right chevron icon',
            last: 'angle double right icon',
          }
        }
      }
    },
  },

  methods: {
    loadPage(page) {
      this.$emit(this.eventPrefix + 'change-page', page)

      const main = document.getElementById('main')

      if (main) {
        main.scrollTop = 0
      }
    },
  },
}
</script>

<style lang="scss">
.vuetable-pagination {
  @apply .flex .justify-center;
  div {
    @apply .flex;

    .c-btn,
    a.c-btn,
    button.c-btn {
      @apply .border-grey-light .rounded-none .inline-block;

      &:focus {
        @apply .z-10 .relative;
      }

      &.active {
        @apply .bg-grey-light;
      }

      &.btn-nav {
        .c-btn-content {
          margin-top: 2px;
        }
      }

      &:first-child {
        @apply .rounded-tl .rounded-bl;
      }

      &:last-child {
        @apply .rounded-tr .rounded-br;
      }

      &:not(:first-child) {
        @apply .border-l-0;
      }

      &:not(.outline) {
        -webkit-box-shadow: none;
        box-shadow: none;
      }

      &[disabled] {
        @apply .opacity-100 .bg-grey-lightest .text-grey;
      }
    }
  }
}
</style>
