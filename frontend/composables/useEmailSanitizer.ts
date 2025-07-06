import DOMPurify from 'isomorphic-dompurify'

export interface EmailSanitizationOptions {
  allowImages?: boolean
  allowStyles?: boolean
  allowLinks?: boolean
  maxImageSize?: number
  allowedDomains?: string[]
}

export const useEmailSanitizer = () => {
  const defaultOptions: EmailSanitizationOptions = {
    allowImages: true,
    allowStyles: true,
    allowLinks: true,
    maxImageSize: 5 * 1024 * 1024, // 5MB
    allowedDomains: []
  }

  /**
   * Sanitize HTML email content to prevent XSS attacks
   */
  const sanitizeEmailContent = (
    htmlContent: string,
    options: EmailSanitizationOptions = {}
  ): string => {
    const config = { ...defaultOptions, ...options }
    
    // Configure DOMPurify for email content
    const purifyConfig: any = {
      ALLOWED_TAGS: [
        'div', 'span', 'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li',
        'table', 'thead', 'tbody', 'tr', 'td', 'th',
        'blockquote', 'pre', 'code',
        'hr', 'sub', 'sup',
        'font', 'center'
      ],
      ALLOWED_ATTR: [
        'style', 'class', 'id',
        'width', 'height', 'cellpadding', 'cellspacing', 'border',
        'align', 'valign', 'bgcolor', 'color',
        'face', 'size'
      ],
      FORBID_TAGS: ['script', 'object', 'embed', 'iframe', 'form', 'input'],
      FORBID_ATTR: ['onload', 'onerror', 'onclick', 'onmouseover'],
      KEEP_CONTENT: true,
      RETURN_DOM: false,
      RETURN_DOM_FRAGMENT: false,
      RETURN_TRUSTED_TYPE: false
    }

    // Handle images
    if (config.allowImages) {
      purifyConfig.ALLOWED_TAGS.push('img')
      purifyConfig.ALLOWED_ATTR.push('src', 'alt', 'title')
    }

    // Handle links
    if (config.allowLinks) {
      purifyConfig.ALLOWED_TAGS.push('a')
      purifyConfig.ALLOWED_ATTR.push('href', 'target', 'rel')
    }

    // Custom hook to process URLs
    DOMPurify.addHook('afterSanitizeAttributes', (node) => {
      // Process images
      if (node.tagName === 'IMG' && config.allowImages) {
        const src = node.getAttribute('src')
        if (src) {
          // Block data URLs that are too large
          if (src.startsWith('data:') && src.length > (config.maxImageSize || 0)) {
            node.removeAttribute('src')
            node.setAttribute('alt', 'Image too large to display safely')
          }
          
          // Ensure external images load securely
          if (src.startsWith('http://')) {
            node.setAttribute('src', src.replace('http://', 'https://'))
          }
        }
      }

      // Process links
      if (node.tagName === 'A' && config.allowLinks) {
        const href = node.getAttribute('href')
        if (href) {
          // Prevent javascript: and data: URLs
          if (href.startsWith('javascript:') || href.startsWith('data:')) {
            node.removeAttribute('href')
          } else {
            // Add security attributes to external links
            node.setAttribute('target', '_blank')
            node.setAttribute('rel', 'noopener noreferrer nofollow')
          }
        }
      }

      // Remove any remaining event handlers
      const attributes = node.attributes
      for (let i = attributes.length - 1; i >= 0; i--) {
        const attr = attributes[i]
        if (attr.name.startsWith('on')) {
          node.removeAttribute(attr.name)
        }
      }
    })

    return DOMPurify.sanitize(htmlContent, purifyConfig)
  }

  /**
   * Sanitize email text content (plain text)
   */
  const sanitizeEmailText = (textContent: string): string => {
    // Basic text sanitization - remove potential script injections
    return textContent
      .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
      .replace(/javascript:/gi, '')
      .replace(/data:text\/html/gi, '')
      .replace(/vbscript:/gi, '')
  }

  /**
   * Extract and sanitize email metadata
   */
  const sanitizeEmailMeta = (meta: Record<string, any>): Record<string, any> => {
    const sanitized: Record<string, any> = {}
    
    const allowedFields = [
      'from', 'to', 'cc', 'bcc', 'subject', 'date', 
      'messageId', 'inReplyTo', 'references'
    ]

    for (const field of allowedFields) {
      if (meta[field]) {
        if (typeof meta[field] === 'string') {
          sanitized[field] = sanitizeEmailText(meta[field])
        } else {
          sanitized[field] = meta[field]
        }
      }
    }

    return sanitized
  }

  /**
   * Create a sandboxed iframe for email content
   */
  const createSandboxedEmailFrame = (sanitizedContent: string): string => {
    const frameId = `email-frame-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
    
    return `
      <iframe 
        id="${frameId}"
        src="data:text/html;charset=utf-8,${encodeURIComponent(sanitizedContent)}"
        sandbox="allow-same-origin"
        style="width: 100%; border: none; background: white;"
        onload="this.style.height = this.contentWindow.document.body.scrollHeight + 'px'"
      ></iframe>
    `
  }

  /**
   * Validate attachment safety
   */
  const validateAttachment = (filename: string, mimeType: string, size: number) => {
    const dangerousExtensions = [
      '.exe', '.scr', '.bat', '.cmd', '.com', '.pif', '.vbs', '.js',
      '.jar', '.app', '.deb', '.pkg', '.rpm', '.dmg', '.iso'
    ]
    
    const allowedMimeTypes = [
      'text/', 'image/', 'application/pdf', 'application/msword',
      'application/vnd.openxmlformats', 'application/vnd.ms-excel',
      'application/vnd.ms-powerpoint', 'application/zip',
      'application/x-zip-compressed'
    ]

    const extension = filename.toLowerCase().substring(filename.lastIndexOf('.'))
    const isDangerous = dangerousExtensions.includes(extension)
    const isMimeAllowed = allowedMimeTypes.some(allowed => mimeType.startsWith(allowed))
    const isSizeOk = size <= 25 * 1024 * 1024 // 25MB limit

    return {
      safe: !isDangerous && isMimeAllowed && isSizeOk,
      reason: isDangerous ? 'Dangerous file type' :
              !isMimeAllowed ? 'Unsupported file type' :
              !isSizeOk ? 'File too large' : null
    }
  }

  return {
    sanitizeEmailContent,
    sanitizeEmailText,
    sanitizeEmailMeta,
    createSandboxedEmailFrame,
    validateAttachment
  }
}