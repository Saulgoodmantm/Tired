# 🚨 TIREDOFDOINTM — COMPLETE FEATURE SPECIFICATION V8 🚨
## Production-Ready Photography Business Platform for Claude Opus 4. 5

---

# 📋 DOCUMENT PURPOSE

This document is the **COMPLETE, AUTHORITATIVE SPECIFICATION** for building the TiredOfDoinTM photography business platform. It is designed for **Claude Opus 4.5** to implement without ambiguity. 

**Target Environment:** Ubuntu VPS (DigitalOcean Droplet)
**Development Environment:** Copilot IDE with C:\Development as root

---

# 🎯 PROJECT IDENTITY

## Brand Information
| Property | Value |
|----------|-------|
| **Name** | `TiredOfDoinTM` (ONE WORD, stylized) |
| **Domain** | `tiredofdointm.com` |
| **Business Type** | Photography studio, model management, creative agency |
| **Contact Email** | `contact@tiredofdointm. com` |

## Social Media
| Platform | URL |
|----------|-----|
| Instagram (Primary) | https://www.instagram.com/tiredofdointm/ |
| Instagram (Secondary) | https://www.instagram.com/tiredflics/ |
| TikTok | https://www.tiktok.com/@tiredofdointm |

## Profile Picture URL
```
https://instagram.fcps3-1.fna.fbcdn. net/v/t51.2885-19/612483021_17897242272370559_4688585427133632962_n.jpg
```

---

# 🔒 GLOBAL SYSTEM RULES

## Rule 1: Permission-First Architecture
Every request MUST: 
1. Identify the user (session/token/guest)
2. Determine their role
3. Check permission for requested resource
4. Allow OR deny with logging

```
Request → Identify User → Check Role → Verify Permission → Proceed/Deny
```

## Rule 2: Auth Gate on All Protected Routes
Every navigation to protected pages must route through `/auth`:
```
User clicks /dashboard → Redirect to /auth? next=/dashboard → Validate session + role → Success/Failure
```

## Rule 3: Data Classification

### Public Data (Never Encrypted, Cacheable)
- Public gallery thumbnails (7 days cache)
- Pricing information (1 hour cache)
- Testimonials (1 day cache)
- Homepage content (1 day cache)

### Private Data (Always Encrypted - AES-256-GCM)
- User credentials
- Session tokens
- Payment data (Stripe handles)
- RAW photographs
- Private galleries
- Contracts
- Messages
- Booking details

## Rule 4: RAW vs Edited Image Separation

### RAW Track
- Original file (CR2, NEF, ARW, DNG)
- Encrypted at rest
- Never publicly accessible
- Stored forever
- Reserved for:  legal proof, AI training, ownership disputes

### Edited Track
- Optimized JPG/WebP
- Multiple sizes (full, web, mobile, thumb