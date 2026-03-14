/**
 * FilterCache Unit Tests
 */

import { describe, test, expect, beforeEach, vi } from 'vitest';
import { FilterCache } from '@canvastack/components/filter/FilterCache';

describe('FilterCache', () => {
    let cache;
    
    beforeEach(() => {
        // Create fresh cache instance before each test
        cache = new FilterCache(1000, 10); // 1 second TTL, max 10 entries
    });
    
    describe('Basic Operations', () => {
        test('should cache and retrieve data', () => {
            const testData = { options: ['A', 'B', 'C'] };
            
            cache.set('key1', testData);
            const retrieved = cache.get('key1');
            
            expect(retrieved).toEqual(testData);
        });
        
        test('should return null for non-existent key', () => {
            const result = cache.get('non-existent-key');
            expect(result).toBeNull();
        });
        
        test('should check if key exists', () => {
            cache.set('key1', { data: 'test' });
            
            expect(cache.has('key1')).toBe(true);
            expect(cache.has('key2')).toBe(false);
        });
        
        test('should clear specific key', () => {
            cache.set('key1', { data: 'test1' });
            cache.set('key2', { data: 'test2' });
            
            cache.clear('key1');
            
            expect(cache.has('key1')).toBe(false);
            expect(cache.has('key2')).toBe(true);
        });
        
        test('should clear all keys', () => {
            cache.set('key1', { data: 'test1' });
            cache.set('key2', { data: 'test2' });
            cache.set('key3', { data: 'test3' });
            
            cache.clearAll();
            
            expect(cache.getStats().size).toBe(0);
        });
    });
    
    describe('TTL (Time To Live)', () => {
        test('should expire old data', async () => {
            cache = new FilterCache(100); // 100ms TTL
            
            cache.set('key1', { data: 'test' });
            expect(cache.get('key1')).toEqual({ data: 'test' });
            
            // Wait for TTL to expire
            await new Promise(resolve => setTimeout(resolve, 150));
            
            expect(cache.get('key1')).toBeNull();
        });
        
        test('should not expire data within TTL', async () => {
            cache = new FilterCache(500); // 500ms TTL
            
            cache.set('key1', { data: 'test' });
            
            // Wait less than TTL
            await new Promise(resolve => setTimeout(resolve, 200));
            
            expect(cache.get('key1')).toEqual({ data: 'test' });
        });
        
        test('should update timestamp on set', async () => {
            cache = new FilterCache(500);
            
            cache.set('key1', { data: 'test1' });
            
            await new Promise(resolve => setTimeout(resolve, 200));
            
            // Update with new data (should reset timestamp)
            cache.set('key1', { data: 'test2' });
            
            await new Promise(resolve => setTimeout(resolve, 200));
            
            // Should still be valid (total 400ms < 500ms TTL)
            expect(cache.get('key1')).toEqual({ data: 'test2' });
        });
    });
    
    describe('Max Size', () => {
        test('should respect max size limit', () => {
            cache = new FilterCache(1000, 3); // Max 3 entries
            
            cache.set('key1', 'data1');
            cache.set('key2', 'data2');
            cache.set('key3', 'data3');
            
            expect(cache.getStats().size).toBe(3);
            
            // Adding 4th entry should remove oldest
            cache.set('key4', 'data4');
            
            expect(cache.getStats().size).toBe(3);
            expect(cache.has('key1')).toBe(false); // Oldest removed
            expect(cache.has('key4')).toBe(true);  // Newest added
        });
        
        test('should remove oldest entry when max size exceeded', () => {
            cache = new FilterCache(1000, 2);
            
            cache.set('key1', 'data1');
            cache.set('key2', 'data2');
            cache.set('key3', 'data3');
            
            expect(cache.has('key1')).toBe(false);
            expect(cache.has('key2')).toBe(true);
            expect(cache.has('key3')).toBe(true);
        });
    });
    
    describe('Key Generation', () => {
        test('should generate consistent keys', () => {
            const key1 = cache.generateKey('column1', { filter1: 'value1' });
            const key2 = cache.generateKey('column1', { filter1: 'value1' });
            
            expect(key1).toBe(key2);
        });
        
        test('should generate different keys for different columns', () => {
            const key1 = cache.generateKey('column1', { filter1: 'value1' });
            const key2 = cache.generateKey('column2', { filter1: 'value1' });
            
            expect(key1).not.toBe(key2);
        });
        
        test('should generate different keys for different parent filters', () => {
            const key1 = cache.generateKey('column1', { filter1: 'value1' });
            const key2 = cache.generateKey('column1', { filter1: 'value2' });
            
            expect(key1).not.toBe(key2);
        });
        
        test('should handle empty parent filters', () => {
            const key1 = cache.generateKey('column1', {});
            const key2 = cache.generateKey('column1', null);
            
            expect(key1).toBe('column1:{}');
            expect(key2).toBe('column1:{}');
        });
        
        test('should sort parent filter keys for consistency', () => {
            const key1 = cache.generateKey('column1', { b: '2', a: '1' });
            const key2 = cache.generateKey('column1', { a: '1', b: '2' });
            
            expect(key1).toBe(key2);
        });
    });
    
    describe('Statistics', () => {
        test('should track cache statistics', () => {
            cache.set('key1', 'data1');
            cache.set('key2', 'data2');
            
            cache.get('key1'); // Hit
            cache.get('key1'); // Hit
            cache.get('key3'); // Miss
            
            const stats = cache.getStats();
            
            expect(stats.size).toBe(2);
            expect(stats.hits).toBe(2);
            expect(stats.misses).toBe(1);
            expect(stats.hitRate).toBeCloseTo(0.67, 2);
        });
        
        test('should calculate hit rate correctly', () => {
            cache.set('key1', 'data1');
            
            cache.get('key1'); // Hit
            cache.get('key1'); // Hit
            cache.get('key1'); // Hit
            cache.get('key2'); // Miss
            
            const stats = cache.getStats();
            
            expect(stats.hitRate).toBe(0.75); // 3 hits / 4 total = 75%
        });
        
        test('should handle zero requests', () => {
            const stats = cache.getStats();
            
            expect(stats.hitRate).toBe(0);
        });
    });
    
    describe('Edge Cases', () => {
        test('should handle null values', () => {
            cache.set('key1', null);
            
            expect(cache.get('key1')).toBeNull();
            expect(cache.has('key1')).toBe(true);
        });
        
        test('should handle undefined values', () => {
            cache.set('key1', undefined);
            
            expect(cache.get('key1')).toBeUndefined();
            expect(cache.has('key1')).toBe(true);
        });
        
        test('should handle complex objects', () => {
            const complexData = {
                options: [
                    { value: '1', label: 'Option 1' },
                    { value: '2', label: 'Option 2' },
                ],
                metadata: {
                    count: 2,
                    timestamp: Date.now(),
                },
            };
            
            cache.set('key1', complexData);
            
            expect(cache.get('key1')).toEqual(complexData);
        });
        
        test('should handle arrays', () => {
            const arrayData = ['A', 'B', 'C', 'D'];
            
            cache.set('key1', arrayData);
            
            expect(cache.get('key1')).toEqual(arrayData);
        });
    });
});
