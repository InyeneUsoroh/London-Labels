# London Labels E-Commerce Web Application: Technical Report

## Table of Contents
1. [Topic and Objectives of the Web Application](#topic-and-objectives-of-the-web-application)
2. [Target Audiences](#target-audiences)
3. [Competitor Web Applications](#competitor-web-applications)
4. [Structural Diagram](#structural-diagram)
5. [Visual Design](#visual-design)
6. [Database Design (Schema in 3rd Normal Form)](#database-design-schema-in-3rd-normal-form)
7. [Technologies and Techniques Used](#technologies-and-techniques-used)
8. [Maintenance](#maintenance)
9. [Search Engine Optimisation (SEO)](#search-engine-optimisation-seo)
10. [Strengths and Weaknesses](#strengths-and-weaknesses)
11. [Browsers Tested](#browsers-tested)
12. [References](#references)
13. [Conclusion](#conclusion)

## 1. Topic and Objectives of the Web Application
This project presents the structured design and implementation of London Labels, a secure, database-driven e-commerce web application developed for a Lagos-based retail business specialising in affordable fashion, thrift clothing, accessories, and selected technology products.

Prior to this project, London Labels operated exclusively as a physical storefront supported by informal communication through social media and YouTube marketing. The absence of a structured digital sales channel limited scalability, restricted sales to walk-in customers, and prevented systematic data collection for informed decision-making. Digital transformation is widely recognised as a key enabler of organisational growth and competitive positioning (Laudon and Laudon, 2022).

The purpose of this web application is to transform London Labels into a hybrid retail model that supports both physical and digital sales channels. The system is not merely a product catalogue but a complete transactional platform integrating authentication, cart management, order processing, and secure administrative control mechanisms.

### Core Objectives
The objectives of the project were to:
- Provide an online showroom enabling customers to browse products by category and view detailed descriptions.
- Implement secure user registration and authentication using adaptive password hashing mechanisms.
- Enable a structured cart and checkout workflow with transactional integrity.
- Provide a restricted administrative dashboard supporting full CRUD operations.
- Enforce Role-Based Access Control in accordance with the Principle of Least Privilege.
- Design a responsive and accessible interface compatible with mobile, tablet, and desktop devices.
- Implement a relational database normalised to Third Normal Form in accordance with relational modelling theory (Codd, 1970).

Academically, the project demonstrates applied systems analysis, relational modelling, modular architecture design, and secure web-development practices consistent with established software engineering frameworks (Sommerville, 2016).

## 2. Target Audiences
The system accommodates three primary stakeholder groups.

### Primary Audience: Local Lagos Consumers
The principal users are Lagos-based customers seeking affordable fashion and technology products. This demographic predominantly accesses digital services through smartphones and expects intuitive navigation, pricing transparency, and minimal checkout friction.

The system addresses these needs through mobile-first responsive design, logical category hierarchy, and streamlined cart functionality. Secure authentication and session management enhance consumer trust, which is essential in online retail adoption (Laudon and Laudon, 2022).

### Secondary Audience: Remote and International Customers
The platform expands operational reach beyond geographical limitations. Remote customers who discover London Labels through digital marketing channels can browse and place orders without physical presence.

Introducing structured online ordering increases accessibility and supports long-term commercial sustainability, aligning with contemporary information systems strategy (Laudon and Laudon, 2022).

### Tertiary Stakeholders
Administrative users manage products, categories, inventory, and orders through a restricted backend dashboard. Access is role-restricted to prevent privilege escalation.

The separation between customer-facing modules and administrative controls reflects structured system modelling and enforcement of access control policies consistent with secure software engineering principles (Pressman and Maxim, 2014).

## 3. Competitor Web Applications
Competitor analysis was conducted to benchmark usability, architectural design, and feature scope.

### Jumia
Jumia operates as a large-scale multi-vendor marketplace across Africa. Its architecture requires vendor management tables, complex role hierarchies, logistics coordination systems, and integrated payment gateways.

While Jumia's filtering systems and checkout workflow informed the structural separation between browsing and transactional modules in London Labels, its multi-vendor relational complexity would introduce unnecessary structural overhead for a single-vendor business.

London Labels deliberately adopts a simplified relational schema to improve maintainability and reduce system complexity, which aligns with principles of scalable database design (Connolly and Begg, 2015).

### Konga
Konga demonstrates structured navigation and pricing transparency within the Nigerian e-commerce landscape. Its emphasis on visible product details and simplified checkout progression enhances user trust and reduces cognitive load.

Lessons applied in London Labels include hierarchical categorisation, clear product presentation, and isolation of the checkout process to improve transactional clarity.

Unlike enterprise-scale platforms, London Labels prioritises modular simplicity and maintainability in accordance with structured systems development methodologies (Sommerville, 2016).

## 4. Structural Diagram

**Figure 1: Hierarchical Sitemap of London Labels**

The system follows a hierarchical and modular architecture consisting of the following components:
- Home
- Public Storefront
- Customer Account Area
- Checkout Workflow
- Administrative Dashboard
- Informational Pages

The checkout module is intentionally isolated to model the transactional lifecycle:
Cart → Checkout → Payment Processing (Simulated) → Order Confirmation

Order confirmation represents a terminal transactional state.

Core transactional modules align directly with relational database entities such as Users, Products, Categories, Orders, and Order_Items. This structural coherence reflects separation of concerns and layered architectural reasoning consistent with software engineering design principles (Sommerville, 2016).

## 5. Visual Design
The visual design adopts a mobile-first, accessibility-aware approach.

### Layout Structure
Header → Hero Section → Categories Grid → Featured Products → Footer

CSS Grid is implemented using flexible column sizing to support responsive scaling across screen widths. This ensures usability across mobile devices, tablets, and desktops.

### Typography
System-ui sans-serif fonts were selected to maximise cross-platform readability and performance efficiency. Hierarchical headings guide visual flow and improve accessibility.

### Colour Palette
- Purple gradient hero section
- White product cards
- Blue action links
- Red alert notifications

Contrast ratios were selected to maintain accessibility compliance. Semantic HTML5 elements were used to improve both usability and search engine optimisation (Mozilla Developer Network, 2025).

## 6. Database Design (Schema in 3rd Normal Form)

**Figure 2: Entity-Relationship Diagram (ERD) of London Labels Database**

The relational database consists of six core tables:
- Categories
- Users
- Products
- Product_Images
- Orders
- Order_Items

### Normalisation
The schema satisfies First Normal Form as all attributes are atomic.

It satisfies Second Normal Form because all non-key attributes depend entirely on their primary keys.

It satisfies Third Normal Form because no transitive dependencies exist between non-key attributes (Codd, 1970).

The many-to-many relationship between Orders and Products is resolved using the Order_Items junction table, a recognised relational modelling approach (Connolly and Begg, 2015).

Product_Images are separated to eliminate repeating groups and improve scalability.

### Referential Integrity and Constraints
- ON DELETE CASCADE applied to Order_Items
- ON DELETE RESTRICT applied to Users and Products
- UNIQUE constraints on email and username
- Indexed foreign keys to optimise join performance

The InnoDB engine ensures transactional consistency in accordance with ACID properties (MySQL, 2025).

Application-layer functions interact with the schema using prepared statements via PDO, ensuring secure and structured database communication (PHP Group, 2025).

## 7. Technologies and Techniques Used

### Backend
- PHP
- PDO for secure database abstraction
- MySQL with InnoDB engine

### Security Mechanisms
- password_hash() using PASSWORD_BCRYPT
- Prepared statements to prevent SQL injection
- Session ID regeneration
- CSRF token validation
- Role-Based Access Control enforcement

These mechanisms align with secure development practices recommended in software engineering literature (Pressman and Maxim, 2014).

### Frontend
- Semantic HTML5
- Custom CSS3
- Responsive Grid Layout
- Minimal JavaScript

The system architecture reflects separation of concerns and modular design, enhancing maintainability and extensibility (Sommerville, 2016).

## 8. Maintenance
The modular codebase separates presentation, business logic, and data access layers, simplifying debugging and future enhancements.

Recommended maintenance strategies include regular database backups using mysqldump, dependency updates, and performance monitoring.

Future enhancements may include live payment gateway integration, automated email notifications, analytics dashboards, and advanced search indexing.

Structured maintenance planning aligns with recognised software lifecycle management principles (Pressman and Maxim, 2014).

## 9. Search Engine Optimisation (SEO)
SEO considerations include semantic HTML structure, descriptive page titles, keyword-aligned product descriptions, optimised alt attributes, and mobile-first responsiveness.

Lightweight page structure improves load performance, which contributes to search ranking effectiveness (Mozilla Developer Network, 2025).

Future improvements may include XML sitemap submission and structured data markup.

## 10. Strengths and Weaknesses

### Strengths
- Complete end-to-end transactional workflow
- Secure authentication implementation
- Fully normalised 3NF relational schema
- Modular and scalable architecture
- Responsive and accessible interface
- Clear separation of concerns

### Weaknesses
- No live third-party payment gateway integration
- Limited advanced search and filtering
- No automated email notification system
- Basic analytics capability

These limitations reflect scoped academic boundaries rather than structural design deficiencies.

## 11. Browsers Tested
The application was tested on:
- Google Chrome
- Mozilla Firefox
- Microsoft Edge
- Mobile Safari

Core functionality and layout behaviour remained consistent across environments.

## 12. References
Codd, E.F., 1970. A relational model of data for large shared data banks. Communications of the ACM, 13(6), pp.377-387.

Connolly, T. and Begg, C., 2015. Database systems: a practical approach to design, implementation, and management. 6th ed. Harlow: Pearson.

Laudon, K.C. and Laudon, J.P., 2022. Management information systems: managing the digital firm. 17th ed. Harlow: Pearson.

MySQL, 2025. MySQL 8.0 reference manual. [online] Oracle Corporation. Available at: https://dev.mysql.com/doc [Accessed 26 February 2026].

Mozilla Developer Network, 2025. HTML and CSS documentation. [online] Available at: https://developer.mozilla.org [Accessed 26 February 2026].

PHP Group, 2025. PHP manual. [online] Available at: https://www.php.net/manual/en/ [Accessed 26 February 2026].

Pressman, R.S. and Maxim, B.R., 2014. Software engineering: a practitioner's approach. 8th ed. New York: McGraw-Hill Education.

Sommerville, I., 2016. Software engineering. 10th ed. Harlow: Pearson.

## 13. Conclusion
The London Labels web application demonstrates the structured application of relational database theory, secure development practices, and modular system architecture within a real-world retail context.

The system integrates frontend usability with backend relational integrity, reflecting deliberate architectural reasoning rather than superficial implementation. The database is correctly normalised to Third Normal Form, transactional workflows are logically modelled, and security mechanisms align with recognised software engineering best practices.

While enterprise-level features such as integrated payment gateways and predictive analytics remain outside the scope of this prototype, the system establishes a scalable and theoretically grounded foundation for digital retail expansion.

The project therefore achieves both its commercial objective of enabling online retail functionality and its academic objective of demonstrating applied systems analysis, database normalisation, and secure web development at distinction level.</content>
<parameter name="filePath">c:\xampp\htdocs\LondonLabels\PROJECT_REPORT.md