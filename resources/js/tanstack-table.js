/**
 * TanStack Table Integration
 * 
 * This file bundles TanStack Table Core and Virtual Core for local usage.
 * Provides table functionality without relying on CDN.
 */

// Import TanStack Table Core
import * as TableCore from '@tanstack/table-core';

// Import TanStack Virtual Core
import * as VirtualCore from '@tanstack/virtual-core';

// Export to window for global access
window.TanStackTable = TableCore;
window.TanStackVirtual = VirtualCore;

// Export for module usage
export { TableCore, VirtualCore };
