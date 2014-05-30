DynamicScaffoldBundle
=====================

A symfony2 bundle that handles dynamic scaffolding the grails way.

Limitations:
  - only works with entity with a single key (e.g. 1 column key)
  - only works with Doctrine
  
  Configuration:
  - entities must each be flagged as scaffoldable with a constant 'SCAFFOLD' with value true
  
 Installation:
  1. drop the bundle in your project and add it to AppKernel
  2. add the routing.yml to your main routing.yml
  3. add the constant 'SCAFFOLD' with value true to the entities that you want to be scaffolded
  4. go to the url '/scaffold'
  5. from there you can browse your bundles, entities and data
  

WARNING, WARNING, WARNING:

!!! never enable this bundle on a public website without authorization
  
  
