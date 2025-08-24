# Task: Redesign MT Evaluation Table Feature Using CSS v4 Framework

  ## Context & Problem Statement
  The `mt-evaluation-table-wrap` component has significant UX/UI issues across all device types (desktop, tablet,
  mobile). The current implementation needs a complete redesign using the newly introduced CSS v4 framework to
  deliver a modern, responsive, and elegant solution. Make sure your work is visible and that the old table is not displayed after your update.

  ## Primary Objectives
  1. **Complete redesign** of the evaluation table feature from scratch
  2. **Responsive implementation** optimized for desktop, tablet, and mobile
  3. **CSS v4 framework integration** following established patterns
  4. **Minimize `!important` usage** through proper CSS architecture
  5. **Enhanced user experience** with modern interaction patterns

  ## Required Preparation Steps
  1. **Codebase Analysis**:
     - Investigate current `mt-evaluation-table-wrap` implementation
     - Read all documentation in `/doc/` directory with particular attention to:
       - CSS v4 framework guidelines
       - Component architecture patterns
       - Responsive design principles

  2. **Knowledge Gathering**:
     - Research modern table design patterns
     - Review current evaluation workflow and user requirements
     - Identify pain points in existing implementation

  ## Implementation Requirements

  ### Technical Specifications
  - **Base URL**: http://localhost:8080/ (jury dashboard front page ID 4996 theme: twenty twenty five no elementor)
  - **Framework**: CSS v4 mobile-first approach
  - **Browser Testing**: Use Kapture MCP and Playwright MCP for comprehensive testing
  - **Responsive Breakpoints**: Desktop (1200px+), Tablet (768-1199px), Mobile (<768px)
  - **CSS Quality**: Reduce `!important` declarations wherever possible
  - **Admin credentials**: nicolas.estrem/Tr@ilBl@z3r#Nic89

  ### Agent Utilization (Deploy in Parallel)
  - `frontend-ui-specialist` - CSS v4 implementation and responsive design
  - `wordpress-code-reviewer` - Code quality and WordPress best practices
  - `security-audit-specialist` - Security review of new implementation
  - `syntax-error-detector` - Code validation and error checking

  ### Testing & Validation
  - **Cross-device testing** using browser automation tools
  - **Performance validation** on various screen sizes
  - **User interaction testing** for all evaluation workflows
  - **CSS specificity optimization** to minimize `!important` usage

  ## Deliverables
  1. **Redesigned evaluation table component** with clean, modern interface
  2. **Fully responsive implementation** working seamlessly across all devices
  3. **CSS v4 framework integration** following established patterns
  4. **Documentation** of design decisions and implementation approach
  5. **Testing report** confirming functionality across target devices

  ## Success Criteria
  - [ ] Evaluation table renders correctly on desktop, tablet, and mobile
  - [ ] User interactions are intuitive and responsive
  - [ ] CSS follows v4 framework conventions
  - [ ] `!important` usage is minimized compared to current implementation
  - [ ] Component passes automated browser testing
  - [ ] Code passes WordPress and security reviews
  - [ ] Your new element is visible and the old table is not in use anymore on desktop tablet and mobile.

  ## Process Instructions
  1. **Use sequential thinking** with 12 structured thought steps to plan approach
  2. **Test continuously** using Kapture MCP and Playwright MCP during development
  3. **Deploy specialized agents** in parallel for comprehensive coverage
  4. **Document decisions** and provide rationale for design choices
  5. **Validate implementation** against all success criteria before completion

  Begin by using the sequential thinking tool to structure your approach, then proceed with codebase investigation
  and documentation review.