# chatgpt_query.py

import sys
import openai
import os
from dotenv import load_dotenv

openai.api_key = os.getenv("OPENAI_API_KEY")

def query_chatgpt(prompt):
    response = openai.Completion.create(
        model="gpt-3.5-turbo-0125",
        prompt=prompt,
        max_tokens=150
    )
    return response.choices[0].text.strip()

if __name__ == "__main__":
    if len(sys.argv) > 1:
        user_input = sys.argv[1]
        result = query_chatgpt(user_input)
        print(result)
