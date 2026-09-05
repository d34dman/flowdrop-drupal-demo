Now applying the competitor replacement rule — well-known Drupal CMS competitors include **WordPress**, **Joomla**, **TYPO3**, **Sitecore**, **Adobe Experience Manager**, **Squarespace**, **Wix**, **Contentful**, **Magnolia**, **Umbraco**, **Sitefinity**, **Kentico**, and **Mambo** (referenced in the source). Scanning the converted markdown, the only competitor name that appears is **Mambo** (referenced in the modules section discussion thread title — not in the main article text) and **WordPress** and **Joomla** do not appear in the converted content. The text is clean of explicit competitor names in the body. Here is the final output:

---

# Drupal

**Drupal** is a free and open-source web content management system (CMS) written in PHP and distributed under the GNU General Public License. Drupal provides an open-source back-end framework for at least 14% of the top 10,000 websites worldwide and 1.2% of the top 10 million websites—ranging from personal blogs to corporate, political, and government sites. Drupal can also be used for knowledge management and for business collaboration.

As of March 2022, the Drupal community had more than 1.39 million members, including 124,000 users actively contributing, resulting in more than 50,000 free modules that extend and customize Drupal functionality, over 3,000 free themes that change the look and feel of Drupal, and at least 1,400 free distributions that allow users to quickly and easily set up a complex, use-specific Drupal in fewer steps.

The base of Drupal is known as **Drupal core**, which contains basic features common to content-management systems. These include user account registration and maintenance, menu management, RSS feeds, taxonomy, page layout customization, and system administration. The Drupal core installation can serve as a simple website, a single or multi-user blog, an Internet forum, or a community website providing for user-generated content.

Drupal also describes itself as a web application framework. When compared with notable frameworks, Drupal meets most of the generally accepted feature requirements for such web frameworks.

Although Drupal offers a sophisticated API for developers, basic Web-site installation and administration of the framework require no programming skills.

Drupal runs on any computing platform that supports both a web server capable of running PHP and a database to store content and configuration.

In 2023/2024, Drupal received over 250,000 Euros from Germany's Sovereign Tech Fund.

Drupal is officially recognized as a Digital Public Good.

## History

Drupal was originally written by Dries Buytaert as a message board for his friends to communicate in their dorms while working on his Master's degree at the University of Antwerp. After graduation, Buytaert moved the site to the public internet and named it Drop.org. Between 2003 and 2008, Buytaert worked towards a PhD degree at Ghent University.

The name *Drupal* represents an English rendering of the Dutch word *druppel*, which means "drop" (as in a water droplet). The name came from the now-defunct Drop.org, whose code slowly evolved into Drupal. Buytaert wanted to call the site "dorp" (Dutch for "village") for its community aspects, but mistyped it when checking the domain name and thought the error sounded better.

Drupal became an open source project in 2001. Interest in Drupal got a significant boost in 2003 when it helped build "DeanSpace" for Howard Dean, one of the candidates in the U.S. Democratic Party's primary campaign for the 2004 U.S. presidential election.

By 2013, the Drupal website listed hundreds of vendors that offered Drupal-related services.

As of 2014, Drupal is developed by a community. From July 2007 to June 2008, the Drupal.org site provided more than 1.4 million downloads of Drupal software, an increase of approximately 125% from the previous year.

As of January 2017, more than 1,180,000 sites use Drupal. Drupal has won several Packt Open Source CMS Awards and won the Webware 100 three times in a row.

Drupal 6 was released on 13 February 2008. Drupal 7 was released on 5 January 2011. Drupal 8 was first released on 19 November 2015. This was the first to use Symfony for components and Twig as a template engine.

Drupal 9 was released in 2020. In October 2022, Drupal released an open source headless CMS accelerator. In April 2023, Drupal was recognized by the United Nations Digital Public Good Alliance as a digital public good.

## Drupal Core

In the Drupal community, "core" refers to the collaboratively built codebase that can be extended through contributory modules.

Core modules also include a hierarchical taxonomy system, which lets developers categorize content or tag with keywords for easier access.

### Core modules

Drupal core includes modules that can be enabled by the administrator to extend the functionality of the core website.

The core Drupal distribution provides a number of features, including:

- Access statistics and logging
- Advanced search
- Books, comments, and forums
- Caching, lazy-loading content (using BigPipe) and feature throttling for improved performance
- Custom content type and fields, and user interface to create, manage, and display lists of content.
- Descriptive URLs
- Multi-level menu system
- Multi-site support
- Multi-user content creation and editing
- RSS feed and feed aggregator
- Security and new release update notification
- User profiles
- Various access control restrictions (user roles, IP addresses, email)
- Workflow tools (triggers and actions)

### Core themes

Drupal includes core themes, which customize the "look and feel" of Drupal sites, for example, Garland and Bartik.

The Color Module, introduced in Drupal core 5.0, allows administrators to change the color scheme of certain themes via a browser interface.

### Drupal CMS

At DrupalCon Portland in 2024, Dries Buytaert called for the Drupal Community to create a new, modernized Drupal experience. The project was initially called Starshot and it was an effort to reframe how people think of Drupal. In 2025, this project was launched as Drupal CMS. This represents a shift toward making Drupal more accessible to non-developers while retaining its powerful, flexible core architecture.

Drupal CMS includes a number of new artificial intelligence features. It also provides tools intended to support open-source, low-code and no-code development approaches.

### Localization

As of September 2022, Drupal is available in 100 languages including English (the default). Support is included for right-to-left languages such as Arabic, Persian, and Hebrew.

Drupal localization is built on top of gettext, the GNU internationalization and localization (i18n) library.

### Auto-update notification

Drupal can automatically notify the administrator about new versions of modules, themes, or the Drupal core.

### Database abstraction

Prior to version 7, Drupal had functions that performed tasks related to databases. Drupal 9 extends the data abstraction layer so that a programmer no longer needs to write SQL queries as text strings.

### Windows development

With Drupal 9's new database abstraction layer, and ability to run on the Windows web server IIS, it is now easier for Windows developers to participate in the Drupal community.

### Accessibility

Since the release of Drupal 7, Web accessibility has been constantly improving in the Drupal community. Drupal is a framework dedicated for building sites accessible to people with disabilities.

Drupal 8 saw many improvements from the Authoring Tool Accessibility Guidelines (ATAG) 2.0 guidelines.

## Extending the core

Drupal core is modular, defining a system of hooks and callbacks, which are accessed internally via an API. This design allows third-party contributed modules and themes to extend or override Drupal's default behaviors without changing Drupal core's code.

Drupal isolates core files from contributed modules and themes. The Drupal community has the saying, "Never hack core," a strong recommendation that site developers not change core files.

### Modules

Contributed modules offer such additional or alternate features as image galleries, custom content types and content listings, WYSIWYG editors, private messaging, third-party integration tools, integrating with BPM portals, and more. As of December 2019, the Drupal website lists more than 44,000 free modules.

Some of the most commonly used contributed modules include:

- **Content Construction Kit (CCK):** Allows site administrators to dynamically create content types by extending the database schema.
- **Views:** Facilitates the retrieval and presentation, through a database abstraction system, of content to site visitors.
- **Panels:** Drag-and-drop layout manager that allows site administrators to visually design their site.
- **Rules:** Conditionally executed actions based on recurring events.
- **Features:** Enables the capture and management of features into custom modules.
- **Context:** Allows the definition of sections of site where Drupal features can be conditionally activated.
- **Media:** Makes photo uploading and media management easier.
- **Services:** Provides an API for Drupal.

### Themes

As of December 2019, there are more than 2,800 free community-contributed themes. Themes adapt or replace a Drupal site's default look and feel.

Drupal themes use standardized formats that may be generated by common third-party theme design engines. Drupal 8 and future versions of Drupal integrate the Twig templating engine.

Community-contributed themes on the Drupal website are released under a free GPL license.

### Distributions

A distribution defines a packaged version of Drupal that upon installation, provides a website or application built for a specific purpose.

The distributions offer the benefit of a new Drupal site without having to manually seek out and install third-party contributed modules or adjust configuration settings.

## Architecture

Drupal is based on the Presentation Abstraction Control architecture, or PAC.

The menu system acts as the Controller. It accepts input via a single source (HTTP GET and POST), routes requests to the appropriate helper functions, pulls data out of the Abstraction (nodes and forms), and then pushes it through a filter to get a Presentation of it (the theme system).

## Community

Drupal.org has a large community of users and developers who provide active community support. As of January 2017, more than 105,400 users are actively contributing. The semiannual DrupalCon conference alternates between North America, Europe and Asia.

Smaller events, known as "Drupal Camps" or DrupalCamp, occur throughout the year all over the world.

There are over 30 national communities around drupal.org offering language-specific support.

By January 2023, The Drop Times became a Drupal-focused media outlet, highlighting stories of relevance to the Drupal community.

### Notable users

Notable users of Drupal include:

- AMD
- Columbia University
- European Commission
- Johnson &amp; Johnson
- McGill University
- NASA
- NBC
- Nokia
- Olympic Games
- Oxford
- Patch
- Pfizer
- Princeton University
- Qualcomm
- Rainforest Alliance
- Smithsonian Institution
- Taboola
- TSMC
- UNICEF
- Universal Music Group
- VISA
- We the People

## Security

Drupal's policy is to announce the nature of each security vulnerability once the fix is released.

Administrators of Drupal sites can be automatically notified of these new releases via the Update Status module (Drupal 6) or via the Update Manager (Drupal 7).

Drupal maintains a security announcement mailing list, a history of all security advisories, a security team home page, and an RSS feed with the most recent security advisories.

In mid-October 2014, Drupal issued a "highly critical" security advisory regarding an SQL injection bug in Drupal 7, also known as Drupalgeddon.

In late March 2018, a patch for vulnerability CVE-2018-7600, also dubbed *Drupalgeddon2*, was released.

On 23 December 2019, Drupal patched an arbitrary file upload flaw.

In September 2022, Drupal announced two security advisories for a severe vulnerability in Twig for users of Drupal 9.3 and 9.4.

In January 2023, Drupal announced software updates to resolve four vulnerabilities in Drupal core and three plugins.

## See also

- Backdrop CMS – Drupal 2013 fork
- List of content management systems

## Further reading

- Abbott/Jones (2016), *Learning Drupal 8*, England, Packt Publishing. ISBN 978-1-78216-875-1
- Pol, Kristen (2012). *Drupal 7 Multilingual Sites*. Birmingham, England: Packt Publishing. ISBN 978-1-84951-818-5.
- Mercer, David (2010). *Drupal 7*. Birmingham, England: Packt Publishing. ISBN 978-1-84951-286-2.
- Travis, Brian (2011). *Pro Drupal 7 for Windows Developers*. Berkeley: APress. ISBN 978-1-4302-3153-0.
- Beighley, Lynn (2009). *Drupal for Dummies*. New York: For Dummies. ISBN 978-0-470-55611-5.

## External links

- Official website: drupal.org