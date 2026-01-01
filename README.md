# EcoVadis Self Assessment WordPress Plugin

## Overview
This WordPress plugin provides a comprehensive EcoVadis-style sustainability self-assessment tool. It evaluates organizations across four key sustainability themes: Environment, Labor & Human Rights, Ethics, and Sustainable Procurement.

## Version
**Version:** 1.0.0  
**Transformed from:** ISO 42001 Gap Analysis v7.1.5

## Assessment Structure

### Question Themes
1. **General (2 questions)**
   - Certifications
   - Reporting

2. **Environment (6 questions)**
   - Energy Policy & Actions
   - Waste Management
   - Environmental Metrics
   - GHG Reporting
   - Renewable Energy

3. **Labor & Human Rights (8 questions)**
   - Health & Safety Policy & Actions
   - Working Conditions
   - Healthcare Coverage
   - Training & Development
   - Anti-Discrimination & DEI
   - Labor Metrics

4. **Ethics (4 questions)**
   - Anti-Corruption Policy & Actions
   - Data Security
   - Ethics Training

5. **Sustainable Procurement (5 questions - only for companies with 25+ employees)**
   - Supplier Policy & Code of Conduct
   - Supplier Assessment
   - Inclusive Sourcing
   - Supplier Metrics

### Total Questions
- **Small/Medium companies (<25 staff):** 20 questions
- **Large companies (25+ staff):** 25 questions (includes Procurement theme)

## Scoring Mechanism

### Point Allocation
- **Option A (Advanced):** 100 points
- **Option B (Basic):** 50 points
- **Option C (Absent):** 0 points

### Impact Weighting
Questions are weighted by their impact level:
- **High Impact:** Score × 1.5
- **Medium Impact:** Score × 1.0
- **Low Impact:** Score × 0.5

### Maximum Scores
- **Small companies:** 2,600 weighted points
- **Large companies:** 3,350 weighted points

### Theme Contribution
- **General:** 7.7% (2 medium-impact questions)
- **Environment:** 26.9% (4 high + 2 medium impact)
- **Labor & Human Rights:** 46.2% (8 high + 1 medium impact)
- **Ethics:** 23.1% (4 high impact)
- **Sustainable Procurement:** 22.7% (5 high impact for large companies)

## Maturity Levels

The assessment classifies organizations into 5 maturity levels:

### Level 1: Initial (0-30%)
- **Characteristics:** Minimal sustainability practices
- **Typical Profile:** Most answers are Option C
- **Priority:** Establish foundational policies and begin systematic tracking

### Level 2: Developing (31-50%)
- **Characteristics:** Basic sustainability framework
- **Typical Profile:** Mix of Options B and C
- **Priority:** Strengthen existing initiatives and expand coverage

### Level 3: Established (51-70%)
- **Characteristics:** Systematic sustainability program
- **Typical Profile:** Predominantly Options B, some A
- **Priority:** Continuous improvement and external verification

### Level 4: Advanced (71-85%)
- **Characteristics:** Integrated sustainability strategy
- **Typical Profile:** Mostly Options A, some B
- **Priority:** Innovation and thought leadership

### Level 5: Leading (86-100%)
- **Characteristics:** Sustainability embedded in business model
- **Typical Profile:** All or nearly all Options A
- **Priority:** Drive innovation and influence value chain

## AI Gap Analysis

The plugin integrates with AI providers to generate comprehensive gap analysis reports:

### Supported AI Providers
1. **DeepSeek** (primary)
2. **Qwen via OpenRouter** (fallback)
3. **Grok via OpenRouter** (fallback)

### Report Structure
The AI generates analysis in 7 sections:
1. **Key Insights** - Overall sustainability posture
2. **Overview** - Summary across themes
3. **Current State** - Existing management system
4. **Risk Implications** - Business risks from gaps
5. **Top Gaps** - 5 most critical gaps
6. **Recommendations** - 5 strategic action steps
7. **Quick Win Actions** - 5 immediate improvements

## Features

### Assessment Flow
1. Organization information collection
2. Company size selection (determines question set)
3. Multi-step questionnaire with progress tracking
4. Real-time scoring calculation
5. AI-powered gap analysis
6. Email delivery of results
7. PDF report generation

### Admin Capabilities
- View all assessment submissions
- Monitor AI API performance
- Configure email templates
- Set up Zapier webhooks
- Database diagnostics
- API monitoring

### Technical Features
- Autosave functionality
- Lead capture and storage
- Encrypted data handling
- Email notifications (user + admin)
- PDF download with expiring tokens
- Zapier integration for CRM sync

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress 'Plugins' menu
3. Configure AI API keys in Settings > EcoVadis Assessment > AI Settings
4. Configure email settings in Settings > EcoVadis Assessment > Email Settings
5. Add the shortcode `[iso42k_assessment]` to any page (note: legacy shortcode name retained for compatibility)

## Configuration

### AI Settings
Configure at least one AI provider:
- DeepSeek API Key
- Qwen via OpenRouter API Key
- Grok via OpenRouter API Key

### Email Settings
- From Name and Email
- Logo URL
- Brand Colors
- Meeting Scheduler URL
- Admin Notification Settings

### Zapier Integration (Optional)
- Enable/Disable webhook
- Webhook URL
- Data inclusion settings (answers, AI analysis)

## Database

### Tables
- `wp_iso42k_leads` - Stores assessment submissions
  - Organization details
  - Assessment answers
  - Score and maturity level
  - AI-generated analysis
  - Timestamps

## Shortcode

```
[iso42k_assessment]
```

Displays the full assessment interface with:
- Introduction step
- Question flow
- Contact information collection
- Results display

## File Structure

```
/data/
  questions.php - Question definitions (returns function accepting company size)

/includes/
  class-iso42k-*.php - Core functionality classes

/public/
  /css/ - Frontend stylesheets
  /js/ - Frontend JavaScript
  /templates/ - HTML templates for assessment steps

/admin/
  /css/ - Admin stylesheets
  /js/ - Admin JavaScript
  /templates/ - Admin page templates
```

## Key Changes from ISO 42001

1. **Questions:** Changed from 33 ISO 42001 technical questions to 20-25 EcoVadis sustainability questions
2. **Themes:** Changed from security/AI themes to sustainability themes (Environment, Labor, Ethics, Procurement)
3. **Scoring:** Changed from 10/5/0 scale to 100/50/0 scale with impact weighting
4. **Maturity Levels:** Changed from Initial/Managed/Established/Optimised to Initial/Developing/Established/Advanced/Leading
5. **Thresholds:** Adjusted maturity level thresholds to EcoVadis standards (30/50/70/85)
6. **AI Prompt:** Updated to focus on sustainability and CSR rather than information security
7. **Branding:** Updated all user-facing text from "ISO 42001" to "EcoVadis"

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile responsive design
- Progressive enhancement approach

## Dependencies

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+

## Security

- Nonce verification on all AJAX requests
- Capability checks for admin functions
- Input sanitization and validation
- SQL injection protection via prepared statements
- XSS protection via output escaping

## Support

For technical support, configuration assistance, or customization inquiries, please contact your administrator.

## License

GPL v2 or later

## Credits

Developed by Your Company
Powered by AI analysis technology

---

**Note:** While the internal class names and constants still use the "iso42k" prefix for backward compatibility and to minimize breaking changes, all user-facing content has been updated to reflect the EcoVadis sustainability assessment framework.
