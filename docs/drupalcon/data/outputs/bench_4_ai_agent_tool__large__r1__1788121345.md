Drupal (/ˈdruːpəl/)[4] is a free and open-source web content management system (CMS) written in PHP and distributed under the GNU General Public License.[3][5][6] Drupal provides an open-source back-end framework for at least 14% of the top 10,000 websites worldwide[7] and 1.2% of the top 10 million websites[8]—ranging from personal blogs to corporate, political, and government sites.[9] Drupal can also be used for knowledge management and for business collaboration.[10]

As of March 2022[update], the Drupal community had more than 1.39 million members,[11][12][13] including 124,000 users actively contributing,[14] resulting in more than 50,000 free modules that extend and customize Drupal functionality,[15] over 3,000 free themes that change the look and feel of Drupal,[16] and at least 1,400 free distributions that allow users to quickly and easily set up a complex, use-specific Drupal in fewer steps.[17]

The base of Drupal is known as **Drupal core**, which contains basic features common to content-management systems. These include user account registration and maintenance, menu management, RSS feeds, taxonomy, page layout customization, and system administration. The Drupal core installation can serve as a simple website, a single or multi-user blog, an Internet forum, or a community website providing for user-generated content.

Drupal also describes itself as a web application framework.[18] When compared with notable frameworks, Drupal meets most of the generally accepted feature requirements for such web frameworks.[19][20]

Although Drupal offers a sophisticated API for developers, basic Web-site installation and administration of the framework require no programming skills.[21]

Drupal runs on any computing platform that supports both a web server capable of running PHP and a database to store content and configuration.

In 2023/2024, Drupal received over 250,000 Euros from Germany's Sovereign Tech Fund.[22][23]

Drupal is officially recognized[24] as a Digital Public Good.

## History

| Version | Release date |
| --- | --- |
| Latest version: **11.4.1**[2] | 3 July 2026[2] |
| Supported: 10.6.2 | 8 January 2026[26] |
| Unsupported: 7.103 | 4 December 2024[27] |
| Unsupported: 9.5.11 | 20 September 2023[28] |
| Unsupported: 8.9.20 | 17 December 2021 [29] |
| Unsupported: 6.38 | 24 February 2016[30] |
| Unsupported: 5.23 | 11 August 2010[31] |
| Unsupported: 4.7.11 | 10 January 2008[32] |
| Unsupported: 3.0 | 15 September 2001[33] |
| Unsupported: 2.0 | 15 March 2001[34] |
| Unsupported: 1.0 | 15 January 2001[35] |
| Unsupported Supported **Latest version** Preview version Future version |

*Latest major and supported releases[25]*

Drupal was originally written by Dries Buytaert as a message board for his friends to communicate in their dorms while working on his Master's degree at the University of Antwerp.[36][37] After graduation, Buytaert moved the site to the public internet and named it Drop.org.[36] Between 2003 and 2008, Buytaert worked towards a PhD degree at Ghent University.[38]

The name *Drupal* represents an English rendering of the Dutch word *druppel*, which means "drop" (as in a water droplet).[39] The name came from the now-defunct Drop.org, whose code slowly evolved into Drupal. Buytaert wanted to call the site "dorp" (Dutch for "village") for its community aspects, but mistyped it when checking the domain name and thought the error sounded better.[40]

Drupal became an open source project in 2001.[40] Interest in Drupal got a significant boost in 2003 when it helped build "DeanSpace" for Howard Dean, one of the candidates in the U.S. Democratic Party)'s primary campaign for the 2004 U.S. presidential election. DeanSpace used open-source sharing of Drupal to support a decentralized network of approximately 50 disparate, unofficial pro-Dean websites that allowed users to communicate directly with one another as well as with the campaign.[41] After Dean ended his campaign, members of his Web team continued to pursue their interest in developing a Web platform that could aid political activism by launching CivicSpace Labs in July 2004, "...the first company with full-time employees that was developing and distributing Drupal technology."[42] Other companies also began to specialize in Drupal development.[43][44]

By 2013, the Drupal website listed hundreds of vendors that offered Drupal-related services.[45]

As of 2014[update], Drupal is developed by a community.[46][*needs update*] From July 2007 to June 2008, the Drupal.org site provided more than 1.4 million downloads of Drupal software, an increase of approximately 125% from the previous year.[47][48]

As of January 2017[update] more than 1,180,000 sites use Drupal.[49] These include hundreds of well-known organizations,[50] including corporations, media and publishing companies, governments, non-profits,[51] schools,[52] and individuals. Drupal has won several Packt Open Source CMS Awards[53] and won the Webware 100 [*clarification needed*] three times in a row.[54][55]

Drupal 6 was released on 13 February 2008,[56] on 5 March 2009, Buytaert announced a code freeze for Drupal 7 for 1 September 2009.[57] Drupal 7 was released on 5 January 2011, with release parties in several countries.[58] After that, maintenance on Drupal 5 stopped, with only Drupal 7 and Drupal 6 maintained.[59] Drupal 7's end-of-life was scheduled for November 2021, but given the impact of COVID-19, and the continuing wide usage, the end of life was pushed back until 1 November 2023.[60] This was extended once more as of June 2023 and was finally set for 5 January 2025.[61]

Drupal 8 was first released on 19 November 2015. This was the first to use Symfony for components and Twig) as a template engine and it also used the Composer) for managing dependencies.[62][63] The last Drupal 8 was version 8.9.20 which was released on 17 December 2021.[29]

Drupal 9 was released in 2020 and was created with easier upgrades and management in mind. The first version was released on 3 June 2020 along with Drupal 8.9.0 with fewer major changes in project structure than in version 8.0, but with some of the old, deprecated code removed.[62][64][65]

In October 2022, Drupal released an open source headless CMS accelerator, allowing the front end to be managed outside of the core system.[66][67]

In April 2023, Drupal was recognized by the United Nations Digital Public Good Alliance as a digital public good.[68]

## Drupal Core

In the Drupal community, "core" refers to the collaboratively built codebase that can be extended through contributory modules and—for versions prior to Drupal 8—is kept outside of the "sites" folder of a Drupal installation.[69] (Starting with version 8, the core is kept in its own 'core' sub-directory.) Drupal core is the stock element of Drupal. Common Drupal-specific libraries, as well as the bootstrap process, are defined as Drupal core; all other functionality is defined as Drupal modules including the system module itself.

In a Drupal website's default configuration, authors can contribute content as either registered or anonymous users (at the discretion of the administrator). This content is accessible to web visitors through a variety of selectable criteria. As of Drupal 8, Drupal has adopted some Symfony libraries into Drupal core.

Core modules also include a hierarchical taxonomy) system, which lets developers categorize content or tag) with keywords for easier access.[21]

### Core modules

Drupal core includes modules that can be enabled by the administrator to extend the functionality of the core website.[70][71]

The core Drupal distribution provides a number of features, including:[70]

- Access statistics and logging
- Advanced search
- Books, comments, and forums
- Caching, lazy-loading content (using BigPipe) and feature throttling for improved performance
- Custom content type and fields, and user interface to create, manage, and display lists of content.
- Descriptive URLs
- Multi-level menu system
- Multi-site support[72]
- Multi-user content creation and editing
- RSS feed and feed aggregator
- Security and new release update notification
- User profiles
- Various access control restrictions (user roles, IP addresses, email)
- Workflow tools (triggers and actions)

### Core themes

Drupal includes core themes, which customize the "look and feel" of Drupal sites,[73] for example, Garland and Bartik.

The Color Module, introduced in Drupal core 5.0, allows administrators to change the color scheme of certain themes via a browser interface.[74]

### Drupal CMS

At DrupalCon Portland in 2024, Dries Buytaert called for the Drupal Community to create a new, modernized Drupal experience. The project was initially called Starshot[75] and it was an effort to reframe how people think of Drupal. The project aims to deliver a more user-friendly and out-of-the-box version of Drupal, with a focus on ease of use, faster onboarding, and a polished default experience. In 2025, this project was launched as Drupal CMS. This represents a shift toward making Drupal more accessible to non-developers while retaining its powerful, flexible core architecture.[76][77]

Drupal CMS includes a number of new artificial intelligence features.[78] It also provides tools intended to support open-source, low-code and no-code development approaches.[79]

### Localization

As of September 2022, Drupal is available in 100 languages including English (the default).[80][81] Support is included for right-to-left languages such as Arabic, Persian, and Hebrew.[82]

Drupal localization is built on top of gettext, the GNU internationalization and localization (i18n) library.

### Auto-update notification

Drupal can automatically notify the administrator about new versions of modules, themes, or the Drupal core.[82] It's important to update quickly after security updates are released.

Before updating it is highly recommended to take backup of core, modules, theme, files and database. If there is any error shown after update or if the new update is not compatible with a module, then it can be quickly replaced by a backup. There are several backup modules available in Drupal.

On 15 October 2014, an SQL injection vulnerability was announced and update was released.[83] Two weeks later the Drupal security team released an advisory explaining that everyone should act under the assumption that any site not updated within 7 hours of the announcement was compromised by automated attacks.[84] Thus, it can be extremely important to apply these updates quickly and usage of a tool like drush to make this process easier is highly recommended.

### Database abstraction

Prior to version 7, Drupal had functions that performed tasks related to databases, such as SQL query cleansing, multi-site table name prefixing, and generating proper SQL queries. In particular, Drupal 6 introduced an abstraction layer that allowed programmers to create SQL queries without writing SQL.

Drupal 9 extends the data abstraction layer so that a programmer no longer needs to write SQL queries as text strings. It uses PHP Data Objects to abstract the database. Microsoft has written a database driver for their SQL Server. Drupal 7 supports the file-based SQLite database engine, which is part of the standard PHP distribution.

### Windows development

With Drupal 9's new database abstraction layer, and ability to run on the Windows web server IIS, it is now easier for Windows developers to participate in the Drupal community.

A group on Drupal.org is dedicated to Windows issues.[85]

### Accessibility

Since the release of Drupal 7, Web accessibility has been constantly improving in the Drupal community.[86] Drupal is a framework dedicated for building sites accessible to people with disabilities because many of the best practices have been incorporated into Drupal Core.

Drupal 8 saw many improvements from the Authoring Tool Accessibility Guidelines (ATAG) 2.0 guidelines which support both an accessible authoring environment as well as support for authors to produce more accessible content.

The accessibility team is carrying on the work of identifying and resolving accessibility barriers and raising awareness within the community.

Drupal 8 has good semantic support for rich web applications through WAI-ARIA. There have been many improvements to both the visitor and administrator sides of Drupal, especially:

- Drag-and-drop functionality
- Improved color contrast and intensity
- Adding skip navigation to core themes
- Adding labels by default for input forms
- Fixing CSS display:none with consistent methods for hiding and exposing text on focus
- Adding support for ARIA Live Regions with Drupal.announce
- Adding a TabbingManager to improve keyboard navigation[87]

The community also added an accessibility gate for core issues in Drupal 8.[88]

## Extending the core

Drupal core is modular, defining a system of hooks and callbacks), which are accessed internally via an API.[89] This design allows third-party contributed [modules](#Modules) and [themes](#Themes) to extend or override Drupal's default behaviors without changing Drupal core's code.

Drupal isolates core files from contributed modules and themes. This increases flexibility and security and allows administrators to cleanly upgrade to new releases without overwriting their site's customizations.[90] The Drupal community has the saying, "Never hack core," a strong recommendation that site developers not change core files.[69]

### Modules

Contributed modules offer such additional or alternate features as image galleries, custom content types and content listings, WYSIWYG editors, private messaging, third-party integration tools,[91] integrating with BPM portals,[92] and more. As of December 2019[update] the Drupal website lists more than 44,000 free modules.[15]

Some of the most commonly used contributed modules include:[93]

- **Content Construction Kit (CCK):** Allows site administrators to dynamically create content types by extending the database schema. "Content type" describes the kind of information. Content types include, but are not limited to, events, invitations, reviews, articles, and products. The CCK Fields API is in Drupal core in Drupal 7.[94][95]
- **Views:** Facilitates the retrieval and presentation, through a database abstraction system, of content to site visitors. Basic views functionality has been added to core of Drupal 8.[96]
- **Panels:** Drag-and-drop layout manager that allows site administrators to visually design their site.
- **Rules:** Conditionally executed actions based on recurring events.
- **Features:** Enables the capture and management of features (entities, views, fields, configuration, etc.) into custom modules.
- **Context:** Allows the definition of sections of site where Drupal features can be conditionally activated
- **Media:** Makes photo uploading and media management easier
- **Services:** Provides an API for Drupal.

### Themes

As of December 2019[update], there are more than 2,800 free community-contributed themes).[16] Themes adapt or replace a Drupal site's default look and feel.

Drupal themes use standardized formats that may be generated by common third-party theme design engines. Many are written in the PHPTemplate engine[97] or, to a lesser extent, the XTemplate engine.[98] Some templates use hard-coded PHP. Drupal 8 and future versions of Drupal integrate the Twig) templating engine.[99]

The inclusion of the PHPTemplate and XTemplate engines in Drupal addressed user concerns about flexibility and complexity.[100] The Drupal theming system utilizes a template engine) to further separate HTML/CSS from PHP. A popular Drupal contributed module called 'Devel' provides GUI information to developers and themers about the page build.

Community-contributed themes on the Drupal website are released under a free GPL license.[101][102]

### Distributions

In the past, those wanting a fully customized installation of Drupal had to download a pre-tailored version separately from the official Drupal core. Today, however, a distribution defines a packaged version of Drupal that upon installation, provides a website or application built for a specific purpose.

The distributions offer the benefit of a new Drupal site without having to manually seek out and install third-party contributed modules or adjust configuration settings.[103] They are collections of modules, themes, and associated configuration settings that prepare Drupal for custom operation. For example, a distribution could configure Drupal as a "brochure" site rather than a news site or online store.

## Architecture

Drupal is based on the Presentation Abstraction Control architecture, or PAC.

The menu system acts as the Controller. It accepts input via a single source (HTTP GET and POST)), routes requests to the appropriate helper functions, pulls data out of the Abstraction (nodes and, from Drupal 5 onwards, forms), and then pushes it through a filter to get a Presentation of it (the theme system).

It even has multiple, parallel PAC agents in the form of blocks that push data out to a common canvas (page.tpl.php).[104]

## Community

Drupal.org has a large community of users and developers who provide active community support by coming up with new updates to help improve the functionality of Drupal.[105] As of January 2017[update] more than 105,400 users are actively contributing.[14] The semiannual DrupalCon conference alternates between North America, Europe and Asia.[106] Attendance at DrupalCon grew from 500 at Szeged in August 2008, to over 3,700 people at Austin, Texas, in June 2014.

Smaller events, known as "Drupal Camps" or DrupalCamp, occur throughout the year all over the world.[107] The annual Florida DrupalCamp brings users together for Coding for a Cause that benefits a local nonprofit organization, as does the annual GLADCamp (Greater Los Angeles Drupal Camp) event, Coders with a Cause.

The Drupal community also organizes professional and semi-professional gatherings called meetups at numerous venues around the world.

There are over 30 national communities[108] around drupal.org offering language-specific support.

By January 2023, The Drop Times became a Drupal-focused media outlet, highlighting stories of relevance to the Drupal community.[109]

### Notable users

Notable users of Drupal include:

- AMD[110]
- Columbia University[111]
- European Commission
- Johnson &amp; Johnson[111]
- McGill University
- NASA[112]
- NBC[113]
- Nokia
- Olympic Games[114]
- Oxford
- Patch
- Pfizer[111]
- Princeton University[111]
- Qualcomm[110]
- Rainforest Alliance[115]
- Smithsonian Institution[111]
- Taboola
- TSMC
- UNICEF[114]
- Universal Music Group[111]
- VISA
- We the People)[116]

## Security

Drupal's policy is to announce the nature of each security vulnerability once the fix is released.[117][118]

Administrators of Drupal sites can be automatically notified of these new releases via the Update Status module (Drupal 6) or via the Update Manager (Drupal 7).[119]

Drupal maintains a security announcement mailing list, a history of all security advisories, a security team home page, and an RSS feed with the most recent security advisories.[120][121][122]

In mid-October 2014, Drupal issued a "highly critical" security advisory regarding an SQL injection bug in Drupal 7, also known as Drupalgeddon.[123][124][125] Downloading and installing an upgrade to Drupal 7.32 fixes the vulnerability, but does not remove any backdoor) installed by hackers if the site has already been compromised).[126] Attacks began soon after the vulnerability was announced. According to the Drupal security team, where a site was not patched within hours of the announcement, it should be considered compromised and taken offline by being replaced with a static HTML page while the administrator of its server must be told that other sites on the same server may also have been compromised. To solve the problem, the site must be restored using backups from before 15 October, be patched and manually updated, and anything merged from the site must be audited.[127]

In late March 2018, a patch for vulnerability CVE-2018-7600, also dubbed *Drupalgeddon2*, was released. The underlying bug allows remote attackers without special roles or permissions to take complete control of Drupal 6, 7, and 8 sites.[128][129] Drupal 6 reached end-of-life on 24 February 2016, and does not get official security updates (extended support is available from two paid Long Term Services Vendors).[130] Starting early April, large scale automated attacks against vulnerable sites were observed, and on 20 April, a high level of penetration of unpatched sites was reported.[131]

On 23 December 2019, Drupal patched an arbitrary file upload flaw. The file-upload flaw affects Drupal 8.8.x before 8.8.1 and 8.7.x before 8.7.11, and the vulnerability is listed as moderately critical by Drupal.[132][133]

In September 2022, Drupal announced two security advisories for a severe vulnerability in Twig for users of Drupal 9.3 and 9.4.[134] That week, Drupal also announced a patch for the S3 File System to fix an access bypass issue.[99]

In January 2023, Drupal announced software updates to resolve four vulnerabilities in Drupal core and three plugins.[135]

## See also

- Backdrop CMS Drupal 2013 fork
- List of content management systems