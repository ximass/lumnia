import json
import sys
import os
from dotenv import load_dotenv
from openai import OpenAI

load_dotenv()

def ask_gpt(prompt):
    client = OpenAI(
        api_key= os.getenv("OPENAI_API_KEY"),
    )

    response = client.chat.completions.create(
        messages=[
            {
                "role": "user",
                "content": prompt,
            }
        ],
        model="gpt-4o",
    )

    result = {
        "id": response.id,
        "object": response.object,
        "created": response.created,
        "model": response.model,
        "choices": [{
            "message": choice.message.content,
            "finish_reason": choice.finish_reason,
            "index": choice.index
        } for choice in response.choices]
    }

    return result

if __name__ == "__main__":
    if len(sys.argv) > 1:
        user_input = sys.argv[1]
        result = ask_gpt(user_input)
        print(json.dumps(result))
