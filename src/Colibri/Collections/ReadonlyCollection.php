<?php

/**
 * Collection with read-only capability.
 * 
 * @author Vahan Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Collections
 * @version 1.0.0
 * 
 */

 namespace Colibri\Collections;

 /**
  * Collection with read-only capability.
  */
 class ReadonlyCollection extends Collection
 {
 
     /**
      * Clears empty values.
      *
      * @return void
      */
     public function Clean(): void
     {
         while (($index = $this->IndexOf('')) > -1) {
             array_splice($this->data, $index, 1);
         }
     }
 
     /**
      * Prevents adding values to the collection.
      *
      * @param string $key
      * @param mixed $value
      * @return void
      * @throws CollectionException
      */
     public function Add(string $key, mixed $value): mixed
     {
         throw new CollectionException('This is a readonly collection');
     }
     /**
      * Prevents deleting values from the collection.
      *
      * @param string $key
      * @return void
      * @throws CollectionException
      */
     public function Delete(string $key): bool
     {
         throw new CollectionException('This is a readonly collection');
     }
 }