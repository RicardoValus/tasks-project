# Tasks Project – Full Stack Architecture Practice
 Overview
Tasks Project is a full stack Task and User Management system developed with a strong focus on backend architecture, database modeling, REST API design, frontend integration, and infrastructure fundamentals.
Although the domain is intentionally simple, the objective is technical depth. The project simulates a real-world company workflow:
Frontend → API → Database → Infrastructure
The emphasis is on understanding architectural decisions, request lifecycle, and system integration rather than building a feature-heavy application.

# Tech Stack
 Backend
PHP 8


Laminas API Tools (Apigility)


 Database
MySQL (relational modeling)


 Frontend
Angular


 Authentication
Token-based authentication (JWT or equivalent strategy)


 Infrastructure
Nginx


PHP-FPM


Debian 12 (local environment structured with production in mind)



# Core Features
User registration and authentication


Token-based route protection


Full CRUD operations for tasks


Relational data modeling (users ↔ tasks)


RESTful endpoint structure


Angular frontend consuming the API via HttpClient


End-to-end integration between frontend, backend, and database



# Architecture Overview
 Nginx handles HTTP requests


 PHP-FPM processes PHP execution


 Laminas API manages business logic and REST endpoints


 MySQL stores relational data


 Angular consumes and interacts with the API


This separation enforces clear responsibility boundaries and mirrors production-ready backend environments.

# Development Approach
The project was built under the following principles:
Functional code over premature optimization


Practical learning through implementation


Clear understanding of request lifecycle


Incremental improvement instead of over-engineering


Focus on backend and architectural maturity



# What I Learned
 Backend Architecture
How REST APIs are structured in a real framework


How Laminas API Tools handles Resources, Collections, and Entities


How request routing and HTTP methods map to business logic


How to separate concerns between controller, service, and persistence layers


 Database Design
Relational modeling with primary and foreign keys


Structuring user-task relationships


Writing and testing CRUD queries


Understanding indexing and query behavior


 Authentication & Security
Implementing token-based authentication


Protecting routes using HTTP headers


Understanding middleware responsibility


Basic API security concepts


 Frontend ↔ Backend Integration
Consuming APIs with Angular HttpClient


Managing environment configuration


Structuring services for API communication


Handling authentication tokens on the frontend


 Infrastructure Fundamentals
Configuring Nginx with PHP-FPM


Understanding how requests flow from web server to application layer


Structuring a Linux-based backend environment


Differences between local development and production setups



# Why This Project Matters
This project demonstrates:
Ability to design and implement REST APIs


Practical knowledge of relational databases


Understanding of full request lifecycle


Experience integrating frontend and backend systems


Familiarity with production-oriented infrastructure concepts


While the domain is simple, the technical foundation reflects real-world backend development practices.


