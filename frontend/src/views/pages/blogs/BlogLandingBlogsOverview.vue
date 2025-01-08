<script setup>

import { themeConfig } from '@themeConfig'
import { can } from '@layouts/plugins/casl'
import keyboard from '@images/svg/keyboard.svg'

const props = defineProps({
  blogs: {
    type: Array,
    required: true,
  },
})

</script>

<template>
  <section>
    <VRow>
      <VCol
        cols="12"
        lg="10"
        class="mx-auto mb-8"
      >
        <VRow>
          <VCol
            v-for="(blog, index) in blogs"
            v-show="index < 3"
            :key="blog.title"
            cols="12"
            md="4"
          >
            <VCard flat border>
              <VCardText class="text-center height-card">
                <VImg
                  :src="blog.image ? themeConfig.settings.urlStorage + blog.image : keyboard"
                  aspect-ratio="1"
                  width="58"
                  class="mx-auto"
                />

                <h6 class="text-h6 my-3 height-card-title">
                  {{ blog.title }}
                </h6>
                <p class="height-card-content" >
                    <span v-if="blog.description !== null">
                        <span v-html="blog.description.slice(0,60)+'...'"></span>
                    </span>
                    <span v-else>
                        {{''}}
                    </span>
                </p>

                <VBtn
                  size="small"
                  variant="tonal"
                  :to="{
                    name: 'dashboard-utils-blogs-category-blog',
                    params: { 
                      category: blog.category.slug, 
                      blog: blog.slug
                    },
                  }"
                >
                  Leer Más
                </VBtn>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCol>
    </VRow>
  </section>
</template>
<style lang="scss" scoped>
  .height-card {
    .height-card-title {
      height: 50px;
    }

    .height-card-content {
      height: 60px;
    }
  }
</style>
