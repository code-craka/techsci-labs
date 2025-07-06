/**
 * Lazy loading composable for components and data
 * 
 * Provides efficient lazy loading strategies for:
 * - Email list virtualization
 * - Image lazy loading
 * - Component lazy loading
 * - Data pagination
 */

export interface LazyLoadOptions {
  threshold?: number
  rootMargin?: string
  once?: boolean
  immediate?: boolean
}

export interface VirtualListOptions {
  itemHeight: number
  buffer?: number
  container?: string
}

export const useLazyLoading = () => {
  /**
   * Lazy load images with Intersection Observer
   */
  const lazyLoadImages = (options: LazyLoadOptions = {}) => {
    const {
      threshold = 0.1,
      rootMargin = '50px',
      once = true
    } = options

    const imageRefs = ref<HTMLImageElement[]>([])
    const loadedImages = ref(new Set<string>())

    const observer = ref<IntersectionObserver | null>(null)

    const createObserver = () => {
      if (typeof window === 'undefined') return

      observer.value = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const img = entry.target as HTMLImageElement
            const src = img.dataset.src
            
            if (src && !loadedImages.value.has(src)) {
              img.src = src
              img.classList.remove('lazy')
              img.classList.add('loaded')
              loadedImages.value.add(src)
              
              if (once) {
                observer.value?.unobserve(img)
              }
            }
          }
        })
      }, {
        threshold,
        rootMargin
      })

      // Observe all existing images
      imageRefs.value.forEach(img => {
        if (img) observer.value?.observe(img)
      })
    }

    const addImage = (el: HTMLImageElement) => {
      if (el && !imageRefs.value.includes(el)) {
        imageRefs.value.push(el)
        observer.value?.observe(el)
      }
    }

    const removeImage = (el: HTMLImageElement) => {
      const index = imageRefs.value.indexOf(el)
      if (index > -1) {
        imageRefs.value.splice(index, 1)
        observer.value?.unobserve(el)
      }
    }

    onMounted(() => {
      createObserver()
    })

    onUnmounted(() => {
      observer.value?.disconnect()
    })

    return {
      addImage,
      removeImage,
      loadedImages: readonly(loadedImages)
    }
  }

  /**
   * Virtual scrolling for large lists
   */
  const useVirtualList = <T>(
    items: Ref<T[]>,
    options: VirtualListOptions
  ) => {
    const {
      itemHeight,
      buffer = 5,
      container = 'window'
    } = options

    const containerRef = ref<HTMLElement>()
    const listRef = ref<HTMLElement>()
    
    const scrollTop = ref(0)
    const containerHeight = ref(0)
    
    const visibleRange = computed(() => {
      const start = Math.floor(scrollTop.value / itemHeight)
      const end = Math.min(
        start + Math.ceil(containerHeight.value / itemHeight) + buffer,
        items.value.length
      )
      
      return {
        start: Math.max(0, start - buffer),
        end
      }
    })

    const visibleItems = computed(() => {
      const { start, end } = visibleRange.value
      return items.value.slice(start, end).map((item, index) => ({
        item,
        index: start + index,
        key: start + index
      }))
    })

    const totalHeight = computed(() => items.value.length * itemHeight)
    const offsetY = computed(() => visibleRange.value.start * itemHeight)

    const handleScroll = (event: Event) => {
      const target = event.target as HTMLElement
      scrollTop.value = target.scrollTop
    }

    const updateContainerHeight = () => {
      if (containerRef.value) {
        containerHeight.value = containerRef.value.clientHeight
      } else if (container === 'window') {
        containerHeight.value = window.innerHeight
      }
    }

    onMounted(() => {
      updateContainerHeight()
      window.addEventListener('resize', updateContainerHeight)
      
      if (container === 'window') {
        window.addEventListener('scroll', handleScroll)
      }
    })

    onUnmounted(() => {
      window.removeEventListener('resize', updateContainerHeight)
      if (container === 'window') {
        window.removeEventListener('scroll', handleScroll)
      }
    })

    return {
      containerRef,
      listRef,
      visibleItems,
      totalHeight,
      offsetY,
      handleScroll,
      visibleRange: readonly(visibleRange)
    }
  }

  /**
   * Lazy load data with pagination
   */
  const useLazyData = <T>(
    fetchFn: (page: number, limit: number) => Promise<{ data: T[], hasMore: boolean }>,
    initialLimit = 20
  ) => {
    const items = ref<T[]>([]) as Ref<T[]>
    const loading = ref(false)
    const hasMore = ref(true)
    const currentPage = ref(1)
    const error = ref<string | null>(null)

    const loadMore = async () => {
      if (loading.value || !hasMore.value) return

      loading.value = true
      error.value = null

      try {
        const result = await fetchFn(currentPage.value, initialLimit)
        
        if (currentPage.value === 1) {
          items.value = result.data
        } else {
          items.value.push(...result.data)
        }
        
        hasMore.value = result.hasMore
        currentPage.value++
      } catch (err) {
        error.value = err instanceof Error ? err.message : 'Failed to load data'
      } finally {
        loading.value = false
      }
    }

    const refresh = async () => {
      currentPage.value = 1
      hasMore.value = true
      items.value = []
      await loadMore()
    }

    const reset = () => {
      items.value = []
      currentPage.value = 1
      hasMore.value = true
      loading.value = false
      error.value = null
    }

    return {
      items: readonly(items),
      loading: readonly(loading),
      hasMore: readonly(hasMore),
      error: readonly(error),
      loadMore,
      refresh,
      reset
    }
  }

  /**
   * Infinite scroll implementation
   */
  const useInfiniteScroll = (
    callback: () => Promise<void> | void,
    options: LazyLoadOptions = {}
  ) => {
    const {
      threshold = 0.1,
      rootMargin = '100px'
    } = options

    const target = ref<HTMLElement>()
    const observer = ref<IntersectionObserver>()

    const createObserver = () => {
      if (typeof window === 'undefined') return

      observer.value = new IntersectionObserver(
        async (entries) => {
          const entry = entries[0]
          if (entry.isIntersecting) {
            await callback()
          }
        },
        {
          threshold,
          rootMargin
        }
      )

      if (target.value) {
        observer.value.observe(target.value)
      }
    }

    onMounted(() => {
      createObserver()
    })

    onUnmounted(() => {
      observer.value?.disconnect()
    })

    watch(target, (newTarget, oldTarget) => {
      if (oldTarget) {
        observer.value?.unobserve(oldTarget)
      }
      if (newTarget) {
        observer.value?.observe(newTarget)
      }
    })

    return {
      target
    }
  }

  /**
   * Component lazy loading with preloading
   */
  const useLazyComponent = (
    componentLoader: () => Promise<any>,
    preload = false
  ) => {
    const component = ref(null)
    const loading = ref(false)
    const loaded = ref(false)
    const error = ref<string | null>(null)

    const loadComponent = async () => {
      if (loaded.value || loading.value) return component.value

      loading.value = true
      error.value = null

      try {
        const loadedComponent = await componentLoader()
        component.value = markRaw(loadedComponent.default || loadedComponent)
        loaded.value = true
      } catch (err) {
        error.value = err instanceof Error ? err.message : 'Failed to load component'
      } finally {
        loading.value = false
      }

      return component.value
    }

    // Preload if requested
    if (preload) {
      onMounted(() => {
        loadComponent()
      })
    }

    return {
      component: readonly(component),
      loading: readonly(loading),
      loaded: readonly(loaded),
      error: readonly(error),
      loadComponent
    }
  }

  /**
   * Resource preloading
   */
  const preloadResource = (href: string, as: string, crossorigin?: string) => {
    if (typeof window === 'undefined') return

    const link = document.createElement('link')
    link.rel = 'preload'
    link.href = href
    link.as = as
    
    if (crossorigin) {
      link.crossOrigin = crossorigin
    }

    document.head.appendChild(link)

    return () => {
      document.head.removeChild(link)
    }
  }

  /**
   * Critical resource hints
   */
  const addResourceHints = (resources: Array<{
    href: string
    rel: 'preload' | 'prefetch' | 'preconnect' | 'dns-prefetch'
    as?: string
    type?: string
    crossorigin?: string
  }>) => {
    if (typeof window === 'undefined') return

    const links: HTMLLinkElement[] = []

    resources.forEach(resource => {
      const link = document.createElement('link')
      link.rel = resource.rel
      link.href = resource.href
      
      if (resource.as) link.as = resource.as
      if (resource.type) link.type = resource.type
      if (resource.crossorigin) link.crossOrigin = resource.crossorigin

      document.head.appendChild(link)
      links.push(link)
    })

    return () => {
      links.forEach(link => {
        if (link.parentNode) {
          link.parentNode.removeChild(link)
        }
      })
    }
  }

  return {
    lazyLoadImages,
    useVirtualList,
    useLazyData,
    useInfiniteScroll,
    useLazyComponent,
    preloadResource,
    addResourceHints
  }
}