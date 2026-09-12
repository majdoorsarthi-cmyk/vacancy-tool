import pymysql
import requests
from bs4 import BeautifulSoup
import re
from datetime import datetime, timedelta

# 1. Smart Category & Keyword Mapper
def determine_correct_category(title):
    t = title.lower()
    if any(k in t for k in ['result', 'selection list', 'mark list', 'scorecard', 'merit list', 'परिणाम']):
        return 'result'
    elif any(k in t for k in ['admit card', 'hall ticket', 'call letter', 'प्रवेश पत्र']):
        return 'admit_card'
    elif any(k in t for k in ['answer key', 'solution', 'उत्तर कुंजी']):
        return 'answer_key'
    elif any(k in t for k in ['syllabus', 'scheme of exam', 'पाठ्यक्रम']):
        return 'syllabus'
    elif any(k in t for k in ['admission', 'counseling', 'seat allotment', 'प्रवेश']):
        return 'admission'
    else:
        return 'job'

# 2. Strict Filter for Real Board Notifications
def fetch_direct_gov_updates():
    sources = [
        {"name": "MPESB Vyapam", "url": "https://esb.mp.gov.in/e_default.html", "state": "Madhya Pradesh"},
        {"name": "MPPSC", "url": "https://mppsc.mp.gov.in/", "state": "Madhya Pradesh"}
    ]
    
    headers = {'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'}
    genuine_posts = []

    valid_keywords = ['recruitment', 'advertisement', 'notification', 'vacancy', 'result', 'admit card', 'exam', 'bharti', 'list', 'mark', 'key', 'answer', 'पद', 'परीक्षा', 'विज्ञप्ति', 'चयन', 'परिणाम']

    for src in sources:
        try:
            res = requests.get(src['url'], headers=headers, timeout=10)
            if res.status_code == 200:
                soup = BeautifulSoup(res.text, 'html.parser')
                
                for a in soup.find_all('a', href=True):
                    text = a.text.strip()
                    href = a['href'].strip()
                    
                    if len(text) < 15 or len(text) > 150:
                        continue
                    
                    text_lower = text.lower()
                    if not any(kw in text_lower for kw in valid_keywords):
                        continue
                        
                    if any(bad in text_lower for bad in ['skip to', 'chairman', 'member', 'structure', 'login', 'contact', 'home', 'about']):
                        continue

                    if not href.startswith('http'):
                        if href.startswith('/'):
                            base_domain = "/".join(src['url'].split('/')[:3])
                            href = base_domain + href
                        else:
                            href = src['url'] + href
                    
                    category = determine_correct_category(text)
                    
                    genuine_posts.append({
                        "title": text,
                        "link": href,
                        "state": src['state'],
                        "category": category
                    })
        except Exception:
            continue
            
    return genuine_posts[:12]

# 3. Database Insertion with Proper Category Routing
def save_clean_post(post):
    try:
        db = pymysql.connect(
            host="100.101.113.86",
            port=3307,
            user="root",
            password="admin123",
            database="job_portal_db",
            charset="utf8mb4"
        )
        cursor = db.cursor()
        cursor.execute("SET time_zone = '+05:30'")
        cursor.execute("SET sql_mode=''")

        cursor.execute("SELECT id FROM vacancies WHERE title = %s", (post['title'],))
        if cursor.fetchone():
            cursor.close()
            db.close()
            return

        default_date = (datetime.now() + timedelta(days=30)).strftime('%Y-%m-%d')
        
        sql = """
        INSERT INTO vacancies (
            title, category, state, board_university, short_desc, 
            location, long_details, description, application_fee, 
            total_posts, eligibility, apply_link, official_website, 
            last_date, status, full_details, required_documents, 
            extra_details, operator_id, created_at
        ) VALUES (
            %s, %s, %s, 'शासकीय बोर्ड / आयोग', %s, 
            %s, %s, %s, 'लागू नहीं', 
            'नवीनतम अपडेट', 'आधिकारिक नियमानुसार', %s, %s, 
            %s, 'live', %s, '1. वैध पहचान पत्र\n2. संबंधित दस्तावेज', 
            'आधिकारिक वेबसाइट पर जाकर विस्तृत जानकारी प्राप्त करें।', 999, NOW()
        )
        """
        
        long_html = f"<h2>{post['title']}</h2><p>यह मध्य प्रदेश शासन/बोर्ड की आधिकारिक वेबसाइट से लिया गया सीधा अपडेट है। अधिक जानकारी के लिए नीचे दिए गए लिंक पर क्लिक करें।</p>"
        full_html = f"<h3>महत्वपूर्ण निर्देश</h3><ul><li>आधिकारिक पोर्टल पर दिए गए निर्देशों को देखें।</li><li>डाउनलोड किए गए दस्तावेज को सुरक्षित रखें।</li></ul>"

        values = (
            post['title'],
            post['category'],  # अब यह बिल्कुल सही कैटेगरी (job, result, admit_card आदि) में जाएगी!
            post['state'],
            f"{post['title']} से संबंधित आधिकारिक सूचना।",
            post['state'],
            long_html,
            post['title'],
            post['link'],
            post['link'],
            default_date,
            full_html
        )

        cursor.execute(sql, values)
        db.commit()
        print(f"[PERFECT SUCCESS] [{post['category'].upper()}] पब्लिश हुआ: {post['title']}")
        cursor.close()
        db.close()
    except Exception as e:
        print("DB Error:", e)

if __name__ == "__main__":
    print("=== Category-Wise Clean Government Scraper Started ===")
    posts = fetch_direct_gov_updates()
    print(f"कुल सही पोस्ट्स मिलीं: {len(posts)}")
    for p in posts:
        save_clean_post(p)
