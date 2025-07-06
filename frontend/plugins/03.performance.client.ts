/**
 * Performance monitoring plugin
 * 
 * Tracks and reports:
 * - Page load times
 * - Core Web Vitals
 * - Resource loading
 * - User interactions
 * - API response times
 */

export default defineNuxtPlugin(() => {
  // Only run on client side
  if (process.server) return

  const performanceMetrics = {
    // Core Web Vitals
    cls: 0,  // Cumulative Layout Shift
    fid: 0,  // First Input Delay
    lcp: 0,  // Largest Contentful Paint
    
    // Load Performance
    fcp: 0,  // First Contentful Paint
    ttfb: 0, // Time to First Byte
    domLoad: 0,
    
    // Custom Metrics
    apiResponseTimes: [] as Array<{ endpoint: string, duration: number }>,
    routeChangeTimes: [] as Array<{ route: string, duration: number }>,
    componentLoadTimes: [] as Array<{ component: string, duration: number }>
  }

  /**
   * Measure Core Web Vitals
   */
  const measureCoreWebVitals = () => {
    // Largest Contentful Paint (LCP)
    if ('PerformanceObserver' in window) {
      const lcpObserver = new PerformanceObserver((list) => {
        const entries = list.getEntries()
        const lastEntry = entries[entries.length - 1] as any
        performanceMetrics.lcp = lastEntry.startTime
        
        // Report LCP when it's stable (no more changes expected)
        if (lastEntry.startTime < 2500) { // Good LCP threshold
          reportMetric('lcp', lastEntry.startTime)
        }
      })
      
      try {
        lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] })
      } catch (e) {
        console.warn('LCP measurement not supported')
      }

      // First Input Delay (FID)
      const fidObserver = new PerformanceObserver((list) => {
        const entries = list.getEntries()
        entries.forEach((entry: any) => {
          performanceMetrics.fid = entry.processingStart - entry.startTime
          reportMetric('fid', performanceMetrics.fid)
        })
      })
      
      try {
        fidObserver.observe({ entryTypes: ['first-input'] })
      } catch (e) {
        console.warn('FID measurement not supported')
      }

      // Cumulative Layout Shift (CLS)
      let clsValue = 0
      const clsObserver = new PerformanceObserver((list) => {
        for (const entry of list.getEntries() as any[]) {
          if (!entry.hadRecentInput) {
            clsValue += entry.value
          }
        }
        performanceMetrics.cls = clsValue
      })
      
      try {
        clsObserver.observe({ entryTypes: ['layout-shift'] })
      } catch (e) {
        console.warn('CLS measurement not supported')
      }
    }
  }

  /**
   * Measure paint timings
   */
  const measurePaintTimings = () => {
    if ('PerformanceObserver' in window) {
      const paintObserver = new PerformanceObserver((list) => {
        const entries = list.getEntries()
        entries.forEach((entry) => {
          if (entry.name === 'first-contentful-paint') {
            performanceMetrics.fcp = entry.startTime
            reportMetric('fcp', entry.startTime)
          }
        })
      })
      
      try {
        paintObserver.observe({ entryTypes: ['paint'] })
      } catch (e) {
        console.warn('Paint timing measurement not supported')
      }
    }
  }

  /**
   * Measure navigation timing
   */
  const measureNavigationTiming = () => {
    if ('performance' in window && 'getEntriesByType' in performance) {
      const navigationEntries = performance.getEntriesByType('navigation') as PerformanceNavigationTiming[]
      
      if (navigationEntries.length > 0) {
        const nav = navigationEntries[0]
        
        performanceMetrics.ttfb = nav.responseStart - nav.requestStart
        performanceMetrics.domLoad = nav.domContentLoadedEventEnd - nav.navigationStart
        
        reportMetric('ttfb', performanceMetrics.ttfb)
        reportMetric('domLoad', performanceMetrics.domLoad)
      }
    }
  }

  /**
   * Monitor API response times
   */
  const monitorApiCalls = () => {
    const originalFetch = window.fetch
    
    window.fetch = async function(input: RequestInfo | URL, init?: RequestInit) {
      const startTime = performance.now()
      const url = typeof input === 'string' ? input : input.toString()
      
      try {
        const response = await originalFetch(input, init)
        const endTime = performance.now()
        const duration = endTime - startTime
        
        // Only track API calls to our backend
        if (url.includes('/api/')) {
          performanceMetrics.apiResponseTimes.push({
            endpoint: url,
            duration
          })
          
          reportMetric('api_response_time', duration, { endpoint: url })
        }
        
        return response
      } catch (error) {
        const endTime = performance.now()
        const duration = endTime - startTime
        
        reportMetric('api_error', duration, { endpoint: url, error: error.message })
        throw error
      }
    }
  }

  /**
   * Monitor route changes
   */
  const monitorRouteChanges = () => {
    const router = useRouter()
    let routeStartTime = performance.now()
    
    router.beforeEach((to, from) => {
      routeStartTime = performance.now()
    })
    
    router.afterEach((to, from) => {
      const duration = performance.now() - routeStartTime
      
      performanceMetrics.routeChangeTimes.push({
        route: to.path,
        duration
      })
      
      reportMetric('route_change', duration, { 
        from: from.path, 
        to: to.path 
      })
    })
  }

  /**
   * Monitor resource loading
   */
  const monitorResourceLoading = () => {
    if ('PerformanceObserver' in window) {
      const resourceObserver = new PerformanceObserver((list) => {
        const entries = list.getEntries()
        entries.forEach((entry) => {
          const resource = entry as PerformanceResourceTiming
          
          // Track slow resources
          if (resource.duration > 1000) { // Slower than 1 second
            reportMetric('slow_resource', resource.duration, {
              name: resource.name,
              type: resource.initiatorType,
              transferSize: resource.transferSize
            })
          }
        })
      })
      
      try {
        resourceObserver.observe({ entryTypes: ['resource'] })
      } catch (e) {
        console.warn('Resource timing measurement not supported')
      }
    }
  }

  /**
   * Monitor long tasks
   */
  const monitorLongTasks = () => {
    if ('PerformanceObserver' in window) {
      const longTaskObserver = new PerformanceObserver((list) => {
        const entries = list.getEntries()
        entries.forEach((entry) => {
          reportMetric('long_task', entry.duration, {
            startTime: entry.startTime
          })
        })
      })
      
      try {
        longTaskObserver.observe({ entryTypes: ['longtask'] })
      } catch (e) {
        console.warn('Long task measurement not supported')
      }
    }
  }

  /**
   * Report metric to analytics/monitoring service
   */
  const reportMetric = (name: string, value: number, attributes: Record<string, any> = {}) => {
    // In development, just log to console
    if (process.env.NODE_ENV === 'development') {
      console.log(`📊 Performance Metric: ${name}`, {
        value: Math.round(value * 100) / 100,
        ...attributes
      })
      return
    }

    // In production, send to your analytics service
    try {
      // Example: Send to Google Analytics 4
      if (typeof gtag !== 'undefined') {
        gtag('event', 'performance_metric', {
          metric_name: name,
          metric_value: Math.round(value),
          ...attributes
        })
      }

      // Example: Send to custom analytics endpoint
      fetch('/api/analytics/performance', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          metric: name,
          value: Math.round(value * 100) / 100,
          timestamp: Date.now(),
          url: window.location.href,
          userAgent: navigator.userAgent,
          ...attributes
        })
      }).catch(() => {
        // Silently fail analytics
      })
    } catch (error) {
      // Silently fail analytics
    }
  }

  /**
   * Get performance summary
   */
  const getPerformanceSummary = () => {
    return {
      coreWebVitals: {
        lcp: performanceMetrics.lcp,
        fid: performanceMetrics.fid,
        cls: performanceMetrics.cls
      },
      loadPerformance: {
        fcp: performanceMetrics.fcp,
        ttfb: performanceMetrics.ttfb,
        domLoad: performanceMetrics.domLoad
      },
      apiPerformance: {
        averageResponseTime: performanceMetrics.apiResponseTimes.length > 0
          ? performanceMetrics.apiResponseTimes.reduce((sum, item) => sum + item.duration, 0) / performanceMetrics.apiResponseTimes.length
          : 0,
        totalRequests: performanceMetrics.apiResponseTimes.length
      },
      routePerformance: {
        averageChangeTime: performanceMetrics.routeChangeTimes.length > 0
          ? performanceMetrics.routeChangeTimes.reduce((sum, item) => sum + item.duration, 0) / performanceMetrics.routeChangeTimes.length
          : 0,
        totalChanges: performanceMetrics.routeChangeTimes.length
      }
    }
  }

  /**
   * Performance budget checking
   */
  const checkPerformanceBudget = () => {
    const budget = {
      lcp: 2500,    // Good LCP is under 2.5s
      fid: 100,     // Good FID is under 100ms
      cls: 0.1,     // Good CLS is under 0.1
      fcp: 1800,    // Good FCP is under 1.8s
      ttfb: 600     // Good TTFB is under 600ms
    }

    const violations = []

    if (performanceMetrics.lcp > budget.lcp) {
      violations.push({ metric: 'LCP', value: performanceMetrics.lcp, budget: budget.lcp })
    }
    if (performanceMetrics.fid > budget.fid) {
      violations.push({ metric: 'FID', value: performanceMetrics.fid, budget: budget.fid })
    }
    if (performanceMetrics.cls > budget.cls) {
      violations.push({ metric: 'CLS', value: performanceMetrics.cls, budget: budget.cls })
    }
    if (performanceMetrics.fcp > budget.fcp) {
      violations.push({ metric: 'FCP', value: performanceMetrics.fcp, budget: budget.fcp })
    }
    if (performanceMetrics.ttfb > budget.ttfb) {
      violations.push({ metric: 'TTFB', value: performanceMetrics.ttfb, budget: budget.ttfb })
    }

    if (violations.length > 0) {
      console.warn('⚠️ Performance Budget Violations:', violations)
      
      violations.forEach(violation => {
        reportMetric('budget_violation', violation.value, {
          metric: violation.metric,
          budget: violation.budget
        })
      })
    }

    return violations
  }

  // Initialize performance monitoring
  onMounted(() => {
    // Wait for page to be interactive
    setTimeout(() => {
      measureCoreWebVitals()
      measurePaintTimings()
      measureNavigationTiming()
      monitorResourceLoading()
      monitorLongTasks()
    }, 100)
    
    monitorApiCalls()
    monitorRouteChanges()
    
    // Check performance budget after page load
    setTimeout(() => {
      checkPerformanceBudget()
    }, 3000)
  })

  // Expose performance utilities globally
  return {
    provide: {
      performance: {
        reportMetric,
        getPerformanceSummary,
        checkPerformanceBudget,
        metrics: performanceMetrics
      }
    }
  }
})