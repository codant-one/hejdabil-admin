<script setup>

const props = defineProps({
  categories: {
    type: Array,
    required: true,
  }
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
            v-for="category in props.categories"
            :key="category.name"
            cols="12"
            sm="6"
            md="4"
          >
            <VCard :title="category.name" v-if="category.blogs_count > 0" class="height-card">
              <template #prepend>
                <VAvatar
                  :icon="category.icon"
                  rounded
                  :color="category.color"
                  variant="tonal"
                />
              </template>
              <VCardText>
                <ul
                  class="ps-6 height-card-content"
                  style="list-style: disc ;"
                >
                  <template v-for="(item, index) in category.blogs" :key="item.title">
                    <li v-if="index < 6" class="text-primary mb-2">
                      <RouterLink
                        :to="{
                          name: 'dashboard-utils-blogs-category-blog',
                          params: { 
                            category: category.slug, 
                            blog: item.slug 
                          },
                        }"
                      >
                        {{ item.title }}
                      </RouterLink>
                    </li>
                  </template>
                </ul>                
                <div class="mt-4">
                  <RouterLink
                    :to="{
                      name: 'dashboard-utils-blogs-category-blog',
                      params: {
                        category: category.slug,
                        blog: category.blogSlug
                      },
                    }"
                    class="text-base font-weight-semibold"
                  >
                    {{ category.blogs_count }} Blogs
                  </RouterLink> 
                </div>
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
      height: 300px;
    }
  }
</style>
