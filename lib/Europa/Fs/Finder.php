<?php

namespace Europa\Fs;

/**
 * Handles the finding of files and directories.
 * 
 * @category Fs
 * @package  Europa
 * @author   Trey Shugart <treshugart@gmail.com>
 * @license  Copyright (c) 2011 Trey Shugart http://europaphp.org/license
 */
class Finder implements \IteratorAggregate
{
    /**
     * The directories to search in.
     * 
     * @var array
     */
    private $dirs = array();
    
    /**
     * The patterns for inclusion.
     * 
     * @var array
     */
    private $is = array();
    
    /**
     * The patterns for exclusion.
     * 
     * @var array
     */
    private $not = array();
    
    /**
     * The custom filters to apply.
     * 
     * @var array
     */
    private $filters = array();
    
    /**
     * How many levels deep to search.
     * 
     * @var int
     */
    private $depth = -1;

    /**
     * Offsets the returned items.
     * 
     * @var int
     */
    private $offset = 0;

    /**
     * Limits the returned items.
     * 
     * @var int
     */
    private $limit = -1;
    
    /**
     * Iterators to prepend to the current finder listing before applying filters.
     * 
     * @var array
     */
    private $prepend = array();
    
    /**
     * Iterators to append to the current finder listing after applying filters.
     * 
     * @var array
     */
    private $append = array();
    
    /**
     * Returns the iterator for the implementation of \IteratorAggregate.
     * 
     * @return \Iterator
     */
    public function getIterator()
    {
        $pre = new \AppendIterator;
        foreach ($this->prepend as $prepend) {
            $pre->append($this->normalizeTraversable($prepend));
        }
        foreach ($this->dirs as $dir) {
            $pre->append($this->getRecursiveIterator($dir));
        }
        
        $post = new \AppendIterator;
        $post->append($this->applyFilters($pre));
        foreach ($this->append as $append) {
            $post->append($this->normalizeTraversable($append));
        }
        
        $it = new Iterator\FsIteratorIterator($post);
        $it->setOffset($this->offset);
        $it->setLimit($this->limit);

        return $it;
    }
    
    /**
     * Prepends an traversable set of items to the finder listing.
     * 
     * @param mixed $prepend The item to prepend.
     * 
     * @return \Europa\Fs\Finder
     */
    public function prepend($prepend)
    {
        $this->prepend[] = $prepend;
        return $this;
    }
    
    /**
     * Appends an traversable set of items to the finder listing.
     * 
     * @param mixed $append The item to append.
     * 
     * @return \Europa\Fs\Finder
     */
    public function append($append)
    {
        $this->append[] = $append;
        return $this;
    }
    
    /**
     * Includes items that match the specified pattern.
     * 
     * @param string $pattern The pattern to match.
     * 
     * @return \Europa\Fs\Finder
     */
    public function is($pattern)
    {
        $this->is[] = $pattern;
        return $this;
    }
    
    /**
     * Excludes files that match the specified pattern.
     * 
     * @param string $pattern The pattern to match.
     * 
     * @return \Europa\Fs\Finder
     */
    public function not($pattern)
    {
        $this->not[] = $pattern;
        return $this;
    }
    
    /**
     * Filters out all directories and leaves only files.
     * 
     * @return \Europa\Fs\Finder
     */
    public function files()
    {
        $this->filter(function($item) {
            return $item->current()->isFile();
        });
        return $this;
    }
    
    /**
     * Filters out all files and leaves only directories.
     * 
     * @return \Europa\Fs\Finder
     */
    public function directories()
    {
        $this->filter(function($item) {
            return $item->current()->isDir();
        });
        return $this;
    }
    
    /**
     * Applies a custom filter to the listing.
     * 
     * @param \Closure $filter The custom filter.
     * 
     * @return \Europa\Fs\Finder
     */
    public function filter(\Closure $filter)
    {
        $this->filters[] = $filter;
        return $this;
    }
    
    /**
     * Adds a path to search in.
     * 
     * @param string $path The path to add to the list of search paths.
     * 
     * @return \Europa\Fs\Finder
     */
    public function in($path)
    {
        if ($real = realpath($path)) {
            $this->dirs[] = $real;
        }
        return $this;
    }
    
    /**
     * Only seeks to the specified hierarchical depth.
     * 
     * @return \Europa\Fs\Finder
     */
    public function depth($depth = null)
    {
        if (is_null($depth) || $depth < -1) {
            $depth = -1;
        }
        $this->depth = $depth;
        return $this;
    }

    /**
     * Offsets the result set.
     * 
     * @param int $offset The offset to use.
     * 
     * @return \Europa\Fs\Finder
     */
    public function offset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }

    /**
     * Limits the result set.
     * 
     * @param int $limit The limit to use.
     * 
     * @return \Europa\Fs\Finder
     */
    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     * Paginates the result set.
     * 
     * @param int $page  The page to use.
     * @param int $limit The limit to use.
     * 
     * @return \Europa\Fs\Finder
     */
    public function page($page, $limit)
    {
        // ensure that the page is a valid value
        $page = $page ? $page : 1;

        // set limit and offse values from page and limit
        $this->limit  = $limit;
        $this->offset = ($page * $limit) - $limit;

        return $this;
    }

    /**
     * Returns an array of path names.
     * 
     * @return array
     */
    public function toArray()
    {
        $arr = array();
        foreach ($this->getIterator() as $item) {
            $arr[] = $item->getPathname();
        }
        return $arr;
    }
    
    /**
     * Does the actual finding and matching for the specified directory.
     * 
     * @param string $dir The directory to search in.
     * 
     * @return \Iterator
     */
    private function applyFilters(\Iterator $iterator)
    {
        $iterator = new Iterator\PathnameFilterIterator($iterator, $this->is, $this->not);
        $iterator = new Iterator\ClosureFilterIterator($iterator, $this->filters);
        return $iterator;
    }
    
    /**
     * Returns the recursive iterator.
     * 
     * @param string $dir The directory to get the recursive iterator for.
     * 
     * @return \RecursiveIteratorIterator
     */
    private function getRecursiveIterator($dir)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        $iterator->setMaxDepth($this->depth);
        return $iterator;
    }
    
    /**
     * Normalizes a traversable item into an iterator.
     * 
     * @param mixed $iterator The iterator to normalize.
     * 
     * @return \Iterator
     */
    private function normalizeTraversable($iterator)
    {
        if ($iterator instanceof \IteratorAggregate) {
            $iterator = $iterator->getIterator();
        } elseif ($iterator instanceof \Iterator) {
            $iterator = $iterator;
        } elseif ($iterator instanceof \Traversable || is_array($iterator)) {
            $traversable = new \ArrayIterator();
            foreach ($iterator as $item) {
                $traversable->append($item instanceof \SplFileInfo ? $item : new \SplFileInfo($item));
            }
            $iterator = $traversable;
        } else {
            throw new \InvalidArgumentException('The specified traversable item cannot be applied to the finder.');
        }
        return $iterator;
    }
}
