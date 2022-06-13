<template>
  <div class="site lg:flex lg:flex-col lg:fixed lg:pin">
    <global-header :showingSidebar="false"></global-header>

    <div class="site-main flex-1 lg:flex">
      <div class="flex-1 bg-blue text-white justify-center items-center flex p-8">
        <div class="w-full max-w-sm">
          <ul class="features list-reset">
            <li>
              <icon
                icon="key"
                size="2xl" />
              Manage your licenses
            </li>
            <li>
              <icon
                icon="plug"
                size="2xl" />
              Publish plugins to the Plugin Store
            </li>
            <li>
              <icon
                icon="line-check-circle"
                size="2xl" />
              Manage your Partner profile
            </li>
          </ul>
        </div>
      </div>

      <div class="flex-1 bg-grey-lighter justify-center items-center flex p-8">
        <div class="w-full max-w-sm">
          <router-view :key="$route.path"></router-view>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import GlobalHeader from '../GlobalHeader'

export default {
  components: {
    GlobalHeader
  }
}
</script>

<style lang="scss">
.site {
  .header {
    @apply .bg-blue .border-b-0;

    &-right {
      & > ul > li > a {
        @apply .text-white;


        &:hover {
          @apply .no-underline .opacity-75 .text-white;
        }
      }
    }

    &-left {
      #sidebar-toggle {
        @apply .hidden;
      }
    }
  }

  .header-brand {
    a {
      @apply .text-white;
      color: #fff !important;
    }
  }

  ul.features {
    @apply .text-base;

    li {
      @apply .py-3;

      .c-icon {
        @apply .text-white .mr-2;
        width: 24px;
        height: 24px;
      }
    }
  }
}

@media (min-width: 992px) {
  .site {
    .header {
      @apply .absolute .w-full .bg-transparent;
      position: absolute;
      border-bottom: 0;

      &-right {
        & > ul > li > a {
          @apply .text-grey-darker;

          &:hover {
            @apply .no-underline .opacity-100 .text-black;
          }
        }
      }
    }

    ul.features {
      @apply .text-xl;

      li {
        @apply .py-6;

        .c-icon {
          width: 1.75rem;
          height: 1.75rem;
        }
      }
    }
  }
}
</style>