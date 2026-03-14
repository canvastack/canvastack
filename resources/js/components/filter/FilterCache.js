/**
 * FilterCache - Frontend caching for filter options.
 * 
 * Provides in-memory caching with TTL (Time To Live) support
 * to reduce API calls and improve performance.
 */
export class FilterCache {
    /**
     * Create a new FilterCache instance.
     * 
     * @param {number} ttl - Time to live in milliseconds (default: 5 minutes)
     * @param {number} maxSize - Maximum cache size (default: 100 entries)
     */
    constructor(ttl = 300000, maxSize = 100) {
        this.cache = new Map();
        this.ttl = ttl;
        this.maxSize = maxSize;
    }
    
    /**
     * Generate cache key from column and parent filters.
     * 
     * @param {string} column - The filter column name
     * @param {Object} parentFilters - The parent filter values
     * @returns {string} Cache key
     */
    generateKey(column, parentFilters = {}) {
        // Sort parent filters by key for consistent cache keys
        const sortedFilters = Object.keys(parentFilters)
            .sort()
            .reduce((acc, key) => {
                acc[key] = parentFilters[key];
                return acc;
            }, {});
        
        return `${column}:${JSON.stringify(sortedFilters)}`;
    }
    
    /**
     * Get cached data.
     * 
     * @param {string} key - Cache key
     * @returns {any|null} Cached data or null if not found/expired
     */
    get(key) {
        const cached = this.cache.get(key);
        
        if (!cached) {
            console.log('Cache miss:', key);
            return null;
        }
        
        // Check if cache has expired
        const now = Date.now();
        const age = now - cached.timestamp;
        
        if (age > this.ttl) {
            console.log('Cache expired:', key, `(age: ${Math.round(age / 1000)}s, TTL: ${this.ttl / 1000}s)`);
            this.cache.delete(key);
            return null;
        }
        
        console.log('Cache hit:', key, `(age: ${Math.round(age / 1000)}s)`);
        return cached.data;
    }
    
    /**
     * Set cached data.
     * 
     * @param {string} key - Cache key
     * @param {any} data - Data to cache
     */
    set(key, data) {
        // Check cache size and remove oldest entries if needed
        if (this.cache.size >= this.maxSize) {
            console.log('Cache size limit reached, removing oldest entries');
            
            // Find oldest entry
            let oldestKey = null;
            let oldestTimestamp = Date.now();
            
            for (const [k, v] of this.cache.entries()) {
                if (v.timestamp < oldestTimestamp) {
                    oldestTimestamp = v.timestamp;
                    oldestKey = k;
                }
            }
            
            // Remove oldest entry
            if (oldestKey) {
                this.cache.delete(oldestKey);
                console.log('Removed oldest cache entry:', oldestKey);
            }
        }
        
        // Store in cache with timestamp
        this.cache.set(key, {
            data: data,
            timestamp: Date.now()
        });
        
        console.log('Cached data:', key, `(cache size: ${this.cache.size})`);
    }
    
    /**
     * Clear all cached data.
     */
    clear() {
        const size = this.cache.size;
        this.cache.clear();
        console.log(`Cache cleared (${size} entries removed)`);
    }
    
    /**
     * Get cache statistics.
     * 
     * @returns {Object} Cache statistics
     */
    getStats() {
        const now = Date.now();
        let totalAge = 0;
        let expiredCount = 0;
        
        for (const [key, value] of this.cache.entries()) {
            const age = now - value.timestamp;
            totalAge += age;
            
            if (age > this.ttl) {
                expiredCount++;
            }
        }
        
        const avgAge = this.cache.size > 0 
            ? Math.round(totalAge / this.cache.size / 1000) 
            : 0;
        
        return {
            size: this.cache.size,
            maxSize: this.maxSize,
            ttl: this.ttl / 1000,
            avgAge: avgAge,
            expiredCount: expiredCount
        };
    }
}
